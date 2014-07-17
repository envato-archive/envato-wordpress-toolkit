/**
 * Author: Japheth Thomson.
 *
 * Forked from original code by Tom McFarlin.

  Copyright 2012 Tom McFarlin (tom@tommcfarlin.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 */

(function ($) {
  "use strict";
  $(function () {

    // Check to see if the Ajax Notification is visible
    if ($('#dismiss-ajax-notification').length > 0) {

      // If so, we need to setup an event handler to trigger it's dismissal
      $('#dismiss-ajax-notification').click(function (evt) {

        evt.preventDefault();

        // Initiate a request to the server-side
        $.post(ajaxurl, {

          // The name of the function to fire on the server
          action: 'hide_admin_notification',

          notice_id: $('#ajax-notification-nonce').parent().attr('id'),

          // The nonce value to send for the security check
          nonce: $.trim($('#ajax-notification-nonce').text())

        }, function (response) {

          // If the response was successful (that is, 1 was returned), hide the notification;
          // Otherwise, we'll change the class name of the notification
          console.log(response);
          if ('1' === response) {
            $('#' + $('#ajax-notification-nonce').parent().attr('id') ).parent().fadeOut('slow');
          } else {

            $('#' + $('#ajax-notification-nonce').parent().attr('id')).parent()
              .removeClass('updated')
              .addClass('error');

          } // end if

        });

      });

    } // end if

  });
}(jQuery));
