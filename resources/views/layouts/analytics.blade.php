{{--
    Page-view analytics, live site only. The tracker sends the page path and the previous page's full address.
    Internal previous addresses are dropped because search text in them can name a patient, and pages whose
    address carries a secret token are never tracked.
--}}
@production
    @unless (request()->routeIs('password.reset', 'verification.verify', 'invitations.*'))
        <script>
            try {
                if (document.referrer && new URL(document.referrer).origin === location.origin) {
                    Object.defineProperty(document, 'referrer', { value: '' });
                }
            } catch (error) {}
        </script>
        <script defer src="https://analytics.aloflux.com/tracker.js" data-site="tunzuri.aloflux.com"></script>
    @endunless
@endproduction
