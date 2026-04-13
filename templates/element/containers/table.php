<script>
    (function() {
        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        window.renderTable = function(headers, rows) {
            let html = '';
            html += '<table border="1" cellpadding="10" cellspacing="0" style="width: 100%; margin-bottom: 30px;">';
            html += '<thead><tr>';

            for (const header of headers) {
                html += '<th>' + escapeHtml(header) + '</th>';
            }

            html += '</tr></thead><tbody>';

            for (const row of rows) {
                html += '<tr>';
                for (const cell of row) {
                    html += '<td>' + cell + '</td>';
                }
                html += '</tr>';
            }

            html += '</tbody></table>';

            return html;
        };
    }());
</script>