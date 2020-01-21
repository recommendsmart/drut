/**
 * @file
 */

(function ($) {
  'use strict';
  // Behavior for scrolling block when navbar is collapsed.
  Drupal.behaviors.BlockScroll = {
    attach(context) {
      $('.navbar-toggle', context).on('click', () => {
        if ($('body', context).hasClass('without-scrolling') && $('html', context).hasClass('without-scrolling')) {
          $('html', context).removeClass('without-scrolling');
          $('body', context).removeClass('without-scrolling');
        }
        else {
          $('html', context).addClass('without-scrolling');
          $('body', context).addClass('without-scrolling');
        }
      });
      $('.menu--main li a', context).on('click', () => {
        if ($('.navbar-collapse', context).hasClass('in')) {
          $('html', context).removeClass('without-scrolling');
          $('body', context).removeClass('without-scrolling');
          $('.navbar-collapse', context).removeClass('in');
          $('.navbar-toggle', context).attr('aria-expanded', 'false');
        }
      });
    }
  };
  // Behavior for hover submenus.
  Drupal.behaviors.Dropdown = {
    attach() {
      const win = $(window);
      const $expanded = $('.expanded.dropdown');
      const $header_expr = $('.header-row .expanded.dropdown');
      const $hero_exp = $('.region-hero-image .expanded.dropdown');
      const dropdown = '.dropdown-menu';
      if (win.width() > 1199) {
        // Show dropdown when item is hovered.
        $hero_exp.on('mouseover', function () {
          $(this).find(dropdown).show();
        });
        $header_expr.on('mouseover', function () {
          $(this).find(dropdown).show();
        });
        $(dropdown).on('mouseover', function () {
          $(this).show();
        });

        // Hide dropdown when item is no more hovered.
        $expanded.on('mouseout', function () {
          $(this).find(dropdown).hide();
        });
      }
      // Remove data-toggle attribute for anchor functionality.
      if ($('ul').hasClass('menu--main')) {
        $('li.dropdown').each(function () {
          $(this).find('a').removeAttr('data-toggle');
        });
      }
    }
  };
  // Behavior for the About Tabs.
  Drupal.behaviors.About = {
    attach(context) {
      // Adding the active class for the first title and first content.
      $('.view-about .view-title-section .views-row', context).eq(0).addClass('active');
      $('.view-about .view-content-section .views-row', context).eq(0).addClass('active');

      // Onclick function to switch the active class from one element to another.
      $('.view-about .view-title-section .views-row', context).on('click', function () {
        const index = $(this).index();
        // Removing the class from the one that has it.
        $('.view-about .views-row', context).removeClass('active');
        // Adding the class to the clicked element.
        $(this).addClass('active');
        $('.view-about .view-content-section .views-row', context).eq(index).addClass('active');
      });
    }
  };
  // Behavior for the Styleguide responsive table.
  Drupal.behaviors.Styleguide = {
    attach(context) {
      // Wraps tables.
      $('table.fixed', context).wrap('<div class=\'scrollable\'></div>');
    }
  };
  Drupal.behaviors.SocialMediaScroll = {
    attach(context) {
      const articleData = $('article .field--name-body', context);
      const sectionData = $('.block-social-media', context);
      $(window).on('scroll', () => {
        if ($(window).width() >= 1200) {
          let startScroll;
          const endScroll = $('article', context).height();
          const ScrollPoss = $(window).scrollTop();
          if (articleData.length === 1) {
            startScroll = articleData.offset().top;
            if ($('body', context).hasClass('user-logged-in')) {
              sectionData.css('top', startScroll - 50);
            }
            else {
              sectionData.css('top', startScroll + 10);
            }
          }
          if (ScrollPoss >= startScroll) {
            sectionData.addClass('scrolled');
          }
          if (ScrollPoss >= endScroll - 350) {
            sectionData.addClass('sticked');
            sectionData.css('top', endScroll - 1500);
          }
          else if (ScrollPoss <= endScroll && ScrollPoss > startScroll) {
            sectionData.removeClass('sticked');
            sectionData.css('top', '');
          }
          else {
            sectionData.removeClass('scrolled');
          }
        }
        else {
          sectionData.removeClass('scrolled');
          sectionData.removeClass('sticked');
          sectionData.css('top', '');
        }
      });
      $(window).on('load', () => {
        if ($(window).width() >= 1200) {
          if (articleData.length === 1) {
            startScroll = articleData.offset().top;
            if ($('body', context).hasClass('user-logged-in')) {
              sectionData.css('top', startScroll - 50);
            }
            else {
              sectionData.css('top', startScroll + 10);
            }
          }
        }
      });
    }
  };
}(jQuery));
