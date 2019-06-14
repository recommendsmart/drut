<?php

namespace Drupal\job_scheduler;

/**
 * Provides an interface defining a job scheduler manager.
 */
interface JobSchedulerInterface {

  /**
   * Returns scheduler info.
   *
   * @param string $name
   *   Name of the schedule.
   *
   * @see hook_cron_job_scheduler_info()
   *
   * @throws JobSchedulerException
   */
  public function info($name);

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
  public function set(array $job);

  /**
   * Removes a job from the schedule, replace any existing job.
   *
   * A job is uniquely identified by $job = array(type, id).
   *
   * @param array $job
   *   A job to reserve.
   */
  public function remove(array $job);

  /**
   * Removes all jobs for a given type.
   *
   * @param string $name
   *   Name of the schedule.
   *
   * @param string $type
   *   The job type to remove.
   */
  public function removeAll($name, $type);

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
   */
  public function dispatch(array $job);

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
  public function execute(array $job);

  /**
   * Re-schedules a job if intended to run again.
   *
   * If cannot determine the next time, drop the job.
   *
   * @param array $job
   *   The job to reschedule.
   */
  public function reschedule(array $job);

  /**
   * Checks whether a job exists in the queue and update its parameters if so.
   *
   * @param array $job
   *   The job to reschedule.
   */
  public function check(array $job);

}
