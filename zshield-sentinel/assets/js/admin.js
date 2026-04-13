(function($) {
    'use strict';

    function initScanFilter() {
        var $table = $('#zss-scan-table');
        if (!$table.length) {
            return;
        }

        var $rows = $table.find('tbody tr');
        var $type = $('#zss-filter-type');
        var $search = $('#zss-filter-search');
        var $count = $('#zss-filter-count');

        function applyFilter() {
            var typeVal = ($type.val() || 'all').toLowerCase();
            var query = ($search.val() || '').toLowerCase();
            var visible = 0;

            $rows.each(function() {
                var $row = $(this);
                var rowType = ($row.data('type') || '').toString().toLowerCase();
                var rowPath = ($row.data('path') || '').toString().toLowerCase();

                var matchesType = (typeVal === 'all') || (rowType === typeVal);
                var matchesQuery = !query || rowPath.indexOf(query) !== -1;

                if (matchesType && matchesQuery) {
                    $row.show();
                    visible++;
                } else {
                    $row.hide();
                }
            });

            if ($count.length) {
                $count.text(visible + ' shown');
            }
        }

        $type.on('change', applyFilter);
        $search.on('input', applyFilter);
        applyFilter();
    }

    function initMalwareFilter() {
        var $table = $('#zss-malware-table');
        if (!$table.length) {
            return;
        }

        var $rows = $table.find('tbody tr');
        var $type = $('#zss-malware-filter');
        var $search = $('#zss-malware-search');
        var $count = $('#zss-malware-count');

        function applyFilter() {
            var typeVal = ($type.val() || 'all').toLowerCase();
            var query = ($search.val() || '').toLowerCase();
            var visible = 0;

            $rows.each(function() {
                var $row = $(this);
                var rowType = ($row.data('severity') || '').toString().toLowerCase();
                var rowPath = ($row.data('path') || '').toString().toLowerCase();

                var matchesType = (typeVal === 'all') || (rowType === typeVal);
                var matchesQuery = !query || rowPath.indexOf(query) !== -1;

                if (matchesType && matchesQuery) {
                    $row.show();
                    visible++;
                } else {
                    $row.hide();
                }
            });

            if ($count.length) {
                $count.text(visible + ' shown');
            }
        }

        $type.on('change', applyFilter);
        $search.on('input', applyFilter);
        applyFilter();
    }

    $(document).ready(function() {
        initScanFilter();
        initMalwareFilter();
    });
})(jQuery);

