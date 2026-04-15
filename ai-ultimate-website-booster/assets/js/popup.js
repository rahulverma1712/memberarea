(function ($) {
    'use strict';

    window.aiwbPopup = {
        init: function (settings) {
            if (!settings || !settings.trigger) {
                return;
            }

            var isMobileDevice = function () {
                return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            };

            if (settings.devices === 'desktop' && isMobileDevice()) {
                return;
            }

            if (settings.devices === 'mobile' && !isMobileDevice()) {
                return;
            }

            var hasShown = false;
            var showPopup = function () {
                if (hasShown) {
                    return;
                }
                hasShown = true;
                $('#aiwb-popup').css('display', 'flex').hide().fadeIn(200);
            };

            var closePopup = function () {
                $('#aiwb-popup').fadeOut(200);
            };

            $(document).on('click', '#aiwb-popup-close', closePopup);

            if ('time_delay' === settings.trigger) {
                setTimeout(showPopup, settings.delay * 1000);
            } else if ('scroll' === settings.trigger) {
                $(window).on('scroll.aiwb', function () {
                    var scrollTop = $(window).scrollTop();
                    var docHeight = $(document).height() - $(window).height();
                    if (docHeight > 0) {
                        var percent = Math.round((scrollTop / docHeight) * 100);
                        if (percent >= settings.scroll) {
                            showPopup();
                            $(window).off('scroll.aiwb');
                        }
                    }
                });
            } else if ('exit_intent' === settings.trigger) {
                $(document).on('mouseleave', function (event) {
                    if (event.clientY < 0) {
                        showPopup();
                    }
                });
            }
        }
    };

    $(document).ready(function () {
        if (typeof aiwbPopupData !== 'undefined') {
            window.aiwbPopup.init(aiwbPopupData);
        }
    });
})(jQuery);
