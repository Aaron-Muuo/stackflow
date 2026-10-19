<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Feedback;

use StackFlow\Widgets\Widget;

/**
 * Toast Notification Widget
 *
 * Renders a dismissible toast notification with automatic timeout, smooth slide/fade animations,
 * color-coded status icons, and customizable positioning.
 *
 * Positions supported:
 *  - 'top-center'    (or 'center-top')
 *  - 'bottom-center' (or 'center-bottom')
 *  - 'top-right'     (default)
 *  - 'top-left'
 *  - 'bottom-right'
 *  - 'bottom-left'
 *
 * Variants supported:
 *  - 'success' (green / emerald)
 *  - 'danger' / 'error' (rose / red)
 *  - 'warning' (amber / yellow)
 *  - 'info'    (indigo / sky)
 *
 * Props:
 *  - message: string (notification text)
 *  - title: ?string (optional bold headline)
 *  - variant: 'success'|'danger'|'error'|'warning'|'info' (default: 'info')
 *  - position: 'top-center'|'bottom-center'|'top-right'|'top-left'|'bottom-right'|'bottom-left' (default: 'top-right')
 *  - duration: int (milliseconds, default: 3500; set 0 for persistent)
 *  - dismissible: bool (show close button, default: true)
 *
 * @package StackFlow\Widgets\Feedback
 */
class Toast extends Widget
{
    public function render(): string
    {
        $message = (string) $this->prop('message', '');
        $title = $this->prop('title');
        $variant = (string) $this->prop('variant', 'info');
        $position = (string) $this->prop('position', 'top-right');
        $duration = (int) $this->prop('duration', 3500);
        $dismissible = (bool) $this->prop('dismissible', true);

        $payload = json_encode([
            'message'     => $message,
            'title'       => $title,
            'variant'     => $variant,
            'position'    => $position,
            'duration'    => $duration,
            'dismissible' => $dismissible,
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        return <<<HTML
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var toastMgr = (window.Stackflow && window.Stackflow.toast) ? window.Stackflow.toast : (window.StackFlow ? window.StackFlow.toast : null);
                if (toastMgr) {
                    toastMgr.show({$payload});
                }
            });
        </script>
        HTML;
    }

    /**
     * Return the complete Toast JavaScript engine and container DOM.
     * Injected automatically into every page by PageRenderer.
     */
    public static function script(): string
    {
        return <<<'HTML'
        <!-- Stackflow Toast Containers -->
        <div id="sf-toast-top-center" class="fixed top-5 left-1/2 -translate-x-1/2 z-[9999] flex flex-col items-center gap-2 pointer-events-none w-auto max-w-md px-4"></div>
        <div id="sf-toast-bottom-center" class="fixed bottom-5 left-1/2 -translate-x-1/2 z-[9999] flex flex-col-reverse items-center gap-2 pointer-events-none w-auto max-w-md px-4"></div>
        <div id="sf-toast-top-right" class="fixed top-5 right-5 z-[9999] flex flex-col items-end gap-2 pointer-events-none w-auto max-w-md px-4 sm:px-0"></div>
        <div id="sf-toast-top-left" class="fixed top-5 left-5 z-[9999] flex flex-col items-start gap-2 pointer-events-none w-auto max-w-md px-4 sm:px-0"></div>
        <div id="sf-toast-bottom-right" class="fixed bottom-12 right-5 z-[9999] flex flex-col-reverse items-end gap-2 pointer-events-none w-auto max-w-md px-4 sm:px-0"></div>
        <div id="sf-toast-bottom-left" class="fixed bottom-5 left-5 z-[9999] flex flex-col-reverse items-start gap-2 pointer-events-none w-auto max-w-md px-4 sm:px-0"></div>

        <script>
        (function() {
            window.Stackflow = window.StackFlow = window.Stackflow || window.StackFlow || {};

            var variantStyles = {
                success: {
                    bg: 'bg-emerald-600 text-white',
                    icon: '<svg class="w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
                },
                danger: {
                    bg: 'bg-rose-600 text-white',
                    icon: '<svg class="w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
                },
                error: {
                    bg: 'bg-rose-600 text-white',
                    icon: '<svg class="w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
                },
                warning: {
                    bg: 'bg-orange-500 text-white',
                    icon: '<svg class="w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>'
                },
                info: {
                    bg: 'bg-blue-600 text-white',
                    icon: '<svg class="w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
                }
            };

            function normalizePosition(pos) {
                pos = (pos || 'top-right').toLowerCase().replace('center-top', 'top-center').replace('center-bottom', 'bottom-center');
                var valid = ['top-center', 'bottom-center', 'top-right', 'top-left', 'bottom-right', 'bottom-left'];
                return valid.indexOf(pos) !== -1 ? pos : 'top-right';
            }

            var toastManager = {
                show: function(opts) {
                    if (typeof opts === 'string') {
                        opts = { message: opts, variant: 'info' };
                    }
                    var message = opts.message || '';
                    var title = opts.title || null;
                    var variant = (opts.variant || opts.type || 'info').toLowerCase();
                    if (variant === 'error') variant = 'danger';
                    var position = normalizePosition(opts.position);
                    var duration = typeof opts.duration === 'number' ? opts.duration : 3500;
                    var dismissible = opts.dismissible !== false;

                    var container = document.getElementById('sf-toast-' + position);
                    if (!container) return;

                    var style = variantStyles[variant] || variantStyles.info;
                    var toast = document.createElement('div');
                    toast.className = 'pointer-events-auto flex items-center gap-2 px-3 py-2 rounded-sm shadow-md ' + style.bg + ' text-sm transition-all duration-200 transform opacity-0 translate-y-1 max-w-sm sm:max-w-md w-auto';

                    var displayText = (title ? escapeHtml(title) + ': ' : '') + escapeHtml(message);
                    var closeBtn = dismissible ? '<button type="button" class="shrink-0 p-0.5 ml-2 text-white/80 hover:text-white rounded hover:bg-black/10 focus:outline-none transition-colors leading-none cursor-pointer" aria-label="Close"><svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>' : '';

                    toast.innerHTML = style.icon +
                        '<span class="flex-1 min-w-0 text-xs sm:text-sm font-medium leading-none truncate">' + displayText + '</span>' +
                        closeBtn;

                    container.appendChild(toast);

                    // Animate in
                    requestAnimationFrame(function() {
                        toast.classList.remove('opacity-0', 'translate-y-1');
                    });

                    function dismiss() {
                        toast.classList.add('opacity-0', 'scale-95');
                        setTimeout(function() {
                            if (toast.parentNode) toast.parentNode.removeChild(toast);
                        }, 200);
                    }

                    if (dismissible) {
                        var btn = toast.querySelector('button');
                        if (btn) btn.onclick = dismiss;
                    }

                    if (duration > 0) {
                        setTimeout(dismiss, duration);
                    }

                    return toast;
                },

                success: function(msg, pos, dur) {
                    return this.show({ message: msg, variant: 'success', position: pos, duration: dur });
                },
                error: function(msg, pos, dur) {
                    return this.show({ message: msg, variant: 'danger', position: pos, duration: dur });
                },
                warning: function(msg, pos, dur) {
                    return this.show({ message: msg, variant: 'warning', position: pos, duration: dur });
                },
                info: function(msg, pos, dur) {
                    return this.show({ message: msg, variant: 'info', position: pos, duration: dur });
                }
            };

            window.Stackflow.toast = window.StackFlow.toast = toastManager;

            function escapeHtml(s) {
                return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
            }
        })();
        </script>
        HTML;
    }
}
