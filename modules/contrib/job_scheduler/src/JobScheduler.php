<?php

namespace Drupal\job_scheduler;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;

/**
 * Manage scheduled jobs.
 */
class JobScheduler implements JobSchedulerInterface {

  /**
   * The current primary database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  public $time;

  /**
   * Constructs a object.
   *
   * @param \Drupal\Core\Database\Connection
   * @param \Drupal\Component\Datetime\TimeInterface $time
   */
  public function __construct(Connection $database, TimeInterface $time) {
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * Returns scheduler info.
   *
   * @param string $name
   *   Name of the schedule.
   *
   * @return array
   *
   * @see hook_cron_job_scheduler_info()
   *
   * @throws JobSchedulerException
   */
  public function info($name) {
    if ($info = job_scheduler_info($name)) {
      return $info;
    }
    throw new JobSchedulerException(t('Could not find Job Scheduler cron information for @name.', [
      '@name' => $name,
    ]));
  }

  /**
   * Adds a job to the schedule, replace any existing job.
   *
   * A job is uniquely identified by $job = array(type, id).
   *
   * @code
   * function worker_callback($job) {
   *   // Work off job.
   *   // Set next time to be called. If this portion of the code is not
   *   // reached for some reason, the scheduler will keep periodically invoking
   *   // the callback() with the period value initially specified.
   *   $scheduler->set($job);
   * }
   * @endcode
   *
   * @param array $job
   *   An array that must contain the following keys:
   *   'type'     - A string identifier of the type of job.
   *   'id'       - A numeric identifier of the job.
   *   'period'   - The time when the task should be executed.
   *   'periodic' - True if the task should be repeated periodically.
   */
  public function set(array $job) {
    $timestamp = $this->time->getRequestTime();
    $job['periodic'] = isset($job['periodic']) ? (int) $job['periodic'] : 0;
    $job['data'] = isset($job['data']) ? serialize($job['data']) : FALSE;
    $job['last'] = $timestamp;
    if (!empty($job['crontab'])) {
      $crontab = new JobSchedulerCronTab($job['crontab']);
      $job['next'] = $crontab->nextTime($timestamp);
    }
    else {
      $job['next'] = $timestamp + $job['period'];
    }

    $job['scheduled'] = 0;
    $this->remove($job);
    $this->database->insert('job_schedule')->fields($job)->execute();
  }

  /**
   * Removes a job from the schedule, replace any existing job.
   *
   * A job is uniquely identified by $job = array(type, id).
   *
   * @param array $job
   *   A job to reserve.
   *
   * @see \Drupal\job_scheduler\JobScheduler::set()
   */
  public function remove(array $job) {
    $this->database->delete('job_schedule')
      ->condition('name', $job['name'])
      ->condition('type', $job['type'])
      ->condition('id', isset($job['id']) ? $job['id'] : 0)
      ->execute();
  }

  /**
   * Removes all jobs for a given type.
   *
   * @param string $name
   *   Name of the schedule.
   *
   * @param string $type
   *   The job type to remove.
   */
  public function removeAll($name, $type) {
    $this->database->delete('job_schedule')
      ->condition('name', $name)
      ->condition('type', $type)
      ->execute();
  }

  /**
   * Dispatches a job.
   *
   * Executes a worker callback or if schedule declares a queue name, queues a
   * job for execution.
   *
   * @param array $job
   *   A $job array as passed into set() or read from job_schedule table.
   *
   * @throws \Exception
   *   Exceptions thrown by code called by this method are passed on.
   *
   * @see \Drupal\job_scheduler\JobScheduler::set()
   */
  public function dispatch(array $job) {
    $info = $this->info($job['name']);
    if (!$job['periodic']) {
      $this->remove($job);
    }
    if (!empty($info['queue name'])) {
      $queue_name = 'job_scheduler_queue:' . $info['queue name'];
      if (\Drupal::queue($queue_name)->createItem($job)) {
        $this->reserve($job);
      }
    }
    else {
      $this->execute($job);
    }
  }

  /**
   * Executes a job.
   *
   * @param array $job
   *   A $job array as passed into set() or read from job_schedule table.
   *
   * @throws \Exception
   *   Exceptions thrown by code called by this method are passed on.
   * @throws \Drupal\job_scheduler\JobSchedulerException
   *   Thrown if the job callback does not exist.
   */
  public function execute(array $job) {
    $info = $this->info($job['name']);
    // If the job is periodic, re-schedule it before calling the worker.
    if ($job['periodic']) {
      $this->reschedule($job);
    }
    if (!empty($info['file']) && file_exists($info['file'])) {
      include_once $info['file'];
    }
    if (function_exists($info['worker callback'])) {
      call_user_func($info['worker callback'], $job);
    }
    else {
      // @todo If worker doesn't exist anymore we should do something about it, remove and throw exception?
      $this->remove($job);
      throw new JobSchedulerException(t('Could not find worker callback function: @function', [
        '@function' => $info['worker callback'],
      ]));
    }
  }

  /**
   * Re-schedules a job if intended to run again.
   *
   * If cannot determine the next time, drop the job.
   *
   * @param array $job
   *   The job to reschedule.
   *
   * @see \Drupal\job_scheduler\JobScheduler::set()
   */
  public function reschedule(array $job) {
    $timestamp = $this->time->getRequestTime();
    $job['periodic'] = isset($job['periodic']) ? (int) $job['periodic'] : 0;
    $job['data'] = isset($job['data']) ? serialize($job['data']) : FALSE;
    $job['last'] = $timestamp;
    $job['scheduled'] = 0;
    if (!empty($job['crontab'])) {
      $crontab = new JobSchedulerCronTab($job['crontab']);
      $job['next'] = $crontab->nextTime($timestamp);
    }
    else {
      $job['next'] = $timestamp + $job['period'];
    }

    if ($job['next']) {
      $this->doUpdate($job, ['item_id']);
    }
    else {
      // If no next time, it may mean it wont run again the next year (crontab).
      $this->remove($job);
    }
  }

  /**
   * Checks whether a job exists in the queue and update its parameters if so.
   *
   * @param array $job
   *   The job to reschedule.
   *
   * @see \Drupal\job_scheduler\JobScheduler::set()
   */
  public function check(array $job) {
    $job += ['id' => 0, 'period' => 0, 'crontab' => ''];

    $existing = $this->database->select('job_schedule')
      ->fields('job_schedule')
      ->condition('name', $job['name'])
      ->condition('type', $job['type'])
      ->condition('id', $job['id'])
      ->execute()
      ->fetchAssoc();
    // If existing, and changed period or crontab, reschedule the job.
    if ($existing) {
      if ($job['period'] != $existing['period'] || $job['crontab'] != $existing['crontab']) {
        $existing['period'] = $job['period'];
        $existing['crontab'] = $job['crontab'];
        $this->reschedule($existing);
      }

      return $existing;
    }
  }

  /**
   * Reserves a job.
   *
   * @param array $job
   *   A job to reserve.
   *
   * @see \Drupal\job_scheduler\JobScheduler::set()
   */
  protected function reserve(array $job) {
    $timestamp = $this->time->getRequestTime();
    $job['periodic'] = isset($job['periodic']) ? (int) $job['periodic'] : 0;
    $job['data'] = isset($job['data']) ? serialize($job['data']) : FALSE;
    $job['scheduled'] = $job['period'] + $timestamp;
    $job['last'] = $timestamp;
    $job['next'] = $job['scheduled'];
    $this->doUpdate($job, ['name', 'type', 'id']);
  }

  /**
   * Updates a record to the database.
   */
  protected function doUpdate(array $job, $primary_keys) {
    $fields = [];
    foreach ($job as $key => $value) {
      if (!in_array($key, $primary_keys)) {
        $fields[$key] = $value;
      }
    }
    $query = $this->database->update('job_schedule')->fields($fields);
    foreach ($primary_keys as $key) {
      if (!isset($job[$key])) {
        throw new JobSchedulerException(t('Could not find job parameter: @parameter', [
          '@parameter' => $key,
        ]));
      }
      $query->condition($key, $job[$key]);
    }
    $query->execute();
  }

}
