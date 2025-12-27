/**
 * Pop-up Uyari Sistemi - JavaScript v4.3
 */
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    console.log('Pop-up: Başlatılıyor...');

    var popup = document.getElementById('popup-overlay');
    if (!popup) {
        console.error('Pop-up: HTML elementi bulunamadı! (Footer hook çalışmamış olabilir)');
        return;
    }

    var closeBtn = document.getElementById('popup-close-btn');
    var closeX = popup.querySelector('.popup-close-x');
    var config = window.popupData || {};
    
    var delay = parseInt(config.delay) || 0;
    var countdown = parseInt(config.countdown) || 3;
    var cookieHours = parseInt(config.cookieHours) || 24;
    var buttonText = config.buttonText || 'Anladim';
    var showOnScroll = parseInt(config.showOnScroll) || 0;
    var scrollPercent = parseInt(config.scrollPercent) || 50;
    var enableStats = parseInt(config.enableStats) || 0;
    
    // Cookie sürümünü güncelledik (v5)
    var cookieName = 'popup_shown_v5';

    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');
        if (parts.length === 2) {
            return parts.pop().split(';').shift();
        }
        return null;
    }

    function setCookie(name, value, hours) {
        var expires = '';
        if (hours > 0) {
            var date = new Date();
            date.setTime(date.getTime() + (hours * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + value + expires + '; path=/; SameSite=Lax';
    }

    function trackStat(type) {
        if (!enableStats || !config.ajaxUrl || !config.nonce) return;
        
        var formData = new FormData();
        formData.append('action', 'popup_track');
        formData.append('nonce', config.nonce);
        formData.append('type', type);
        
        if (navigator.sendBeacon) {
            formData.append('is_beacon', '1');
            fetch(config.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                keepalive: true
            }).catch(function() {});
        } else {
            fetch(config.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            }).catch(function() {});
        }
    }

    function showPopup() {
        if (getCookie(cookieName)) {
            console.log('Pop-up: Cookie mevcut, gösterilmeyecek.');
            return;
        }
        
        console.log('Pop-up: Gösteriliyor...');
        
        popup.style.display = 'flex';
        // Force reflow
        void popup.offsetWidth;
        
        popup.classList.remove('popup-hidden');
        popup.classList.add('popup-visible');
        
        trackStat('view');
        
        if (closeBtn && countdown > 0) {
            var remaining = countdown;
            closeBtn.disabled = true;
            closeBtn.innerHTML = buttonText + ' <span class="countdown">' + remaining + '</span>';
            
            var timer = setInterval(function() {
                remaining--;
                if (remaining > 0) {
                    closeBtn.innerHTML = buttonText + ' <span class="countdown">' + remaining + '</span>';
                } else {
                    clearInterval(timer);
                    closeBtn.innerHTML = buttonText;
                    closeBtn.disabled = false;
                }
            }, 1000);
        } else if (closeBtn) {
            closeBtn.disabled = false;
            closeBtn.innerHTML = buttonText;
        }
        
        if (cookieHours > 0) {
            setCookie(cookieName, '1', cookieHours);
        }
        
        document.body.style.overflow = 'hidden';
    }

    function hidePopup() {
        console.log('Pop-up: Kapatılıyor...');
        popup.classList.remove('popup-visible');
        
        setTimeout(function() {
            popup.style.display = 'none';
            popup.classList.add('popup-hidden');
            document.body.style.overflow = '';
        }, 500);
        
        trackStat('close');
    }

    var scrollTriggered = false;
    function handleScroll() {
        if (scrollTriggered) return;
        
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        var docHeight = document.documentElement.scrollHeight - window.innerHeight;
        
        if (docHeight <= 0) return;
        
        var scrolled = (scrollTop / docHeight) * 100;
        
        if (scrolled >= scrollPercent) {
            scrollTriggered = true;
            window.removeEventListener('scroll', handleScroll);
            showPopup();
        }
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!this.disabled) {
                hidePopup();
            }
        });
    }
    
    if (closeX) {
        closeX.addEventListener('click', function(e) {
            e.preventDefault();
            hidePopup();
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && popup.classList.contains('popup-visible')) {
            if (closeBtn && !closeBtn.disabled) {
                hidePopup();
            } else if (closeX) {
                hidePopup();
            }
        }
    });

    if (getCookie(cookieName)) {
        console.log('Pop-up: Zaten görüldü (Cookie).');
        return;
    }

    if (showOnScroll) {
        console.log('Pop-up: Scroll bekleniyor (%' + scrollPercent + ')...');
        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();
    } else if (exitIntent) {
        console.log('Pop-up: Cikis niyeti (Exit Intent) bekleniyor...');
        document.addEventListener('mouseleave', function(e) {
            if (e.clientY <= 0) {
                showPopup();
            }
        });
    } else {
        console.log('Pop-up: Gecikme süresi bekleniyor (' + delay + 'sn)...');
        setTimeout(showPopup, delay * 1000);
    }
});
