    {{-- GA4, same property the previous homepage used, so the timeline is
         continuous across the redesign rather than starting over. --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-174D73GPWB"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-174D73GPWB');

        // The sign-in CTA is same-origin, so GA's enhanced measurement will not
        // catch it. Without this event there is nothing to compare but pageviews.
        document.addEventListener('click', function (e) {
            var a = e.target.closest('a[href*="/auth/google"], a[href*="/auth/dropbox"]');
            if (!a) return;
            gtag('event', 'sign_in_click', {
                event_category: 'conversion',
                event_label: a.dataset.cta || 'unknown'
            });
        });
    </script>
