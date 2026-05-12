<?php
class qa_activity_heatmap_widget {
    function allow_template($template) { return ($template === 'user'); }
    function allow_region($region) { return true; }

    function get_exam_data($userid) {
        $result = qa_db_read_all_assoc(qa_db_query_sub(
            "SELECT DATE(datetime) AS exam_date, COUNT(*) AS exams_given
            FROM qa_exam_results
            WHERE userid = #
            GROUP BY DATE(datetime)
            ORDER BY DATE(datetime) DESC",
            $userid
        ));

        $data = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                $timestamp = strtotime($row['exam_date']);
                $exams = (int)$row['exams_given'];
                if ($timestamp !== false) {
                    $data[] = [
                        'date' => $timestamp*1000,
                        'value' => $exams
                    ];
                }
            }
        } else {
            // handling no exam case
            $data[] = [
                'date' => time() * 1000,
                'value' => null
            ];
        }

        return $data;
    }

    function output_widget($region, $place, $themeobject, $template, $request, $qa_content) {
        $handle = qa_request_part(1); 
        $target_userid = qa_handle_to_userid($handle);
        $ajax_url = qa_path('exam-stats-ajax');
        
        if (!$target_userid) {
            $themeobject->output('<div class="qa-widget-content">No user found.</div>');
            return;
        }

        $loggedin_userid = qa_get_logged_in_userid();
        $is_owner = ($loggedin_userid == $target_userid);
        $is_admin = (qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN);

        // Privacy check: public by default (heatmap_private NULL/0 = public, 1 = private)
        $is_private = (int)qa_db_usermeta_get($target_userid, 'heatmap_private');
        if ($is_private && !$is_owner && !$is_admin) {
            return;
        }

        $exam_data = $this->get_exam_data($target_userid); 
        $json = json_encode($exam_data);

        // Privacy toggle button for owner (AJAX-based)
        $privacy_btn = '';
        if ($is_owner) {
            $lock_icon = $is_private ? '&#128274;' : '&#128275;';
            $label = $is_private ? 'Private' : 'Public';
            $privacy_btn = '<button type="button" id="heatmap-privacy-btn" class="qa-heatmap-privacy-btn" title="Toggle privacy" data-private="' . ($is_private ? '1' : '0') . '">' . $lock_icon . ' ' . $label . '</button>';
        }
        $admin_note = (!$is_owner && $is_admin && $is_private) ? ' <span style="color:#c00;font-size:12px;">(Hidden from public)</span>' : '';

        $themeobject->output('<div id="activity-heatmap-tooltip" class="ch-plugin-tooltip"></div>');
        $themeobject->output('
            <div class="qa-heatmap-title-row">
                <span>Activity Heatmap' . $admin_note . '</span>
                <span>' . $privacy_btn . '</span>
            </div>
            <div style="position: relative; padding: 10px 75px 0px 5px; margin-bottom: 5px; text-align: right;">
                <select id="year-filter">
                    <option value="default">Default (Last 12 months)</option>
                </select>
            </div>
            <div id="activity-heatmap"></div>
        ');

        $themeobject->output("
        <script>
        // Store the data globally so the layer can access it
        window.examHeatmapData = $json;
        window.calInstance = null;
        
        // Function to populate year dropdown
        function populateYearDropdown() {
            const select = document.getElementById('year-filter');
            const currentYear = new Date().getFullYear();
            
            // Clear existing options except the first one (Default)
            while (select.options.length > 1) {
                select.remove(1);
            }
            
            // Add last 5 years
            for (let i = 0; i < 5; i++) {
                const year = currentYear - i;
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                select.appendChild(option);
            }
        }
        
        // Function to get date range based on selection
        function getDateRange(value) {
            const now = new Date();
            let startDate, endDate, range;
            
            if (value === 'default') {
                // Last 12 months
                startDate = new Date(now.getFullYear()-1, now.getMonth()+1, 2);
                endDate = new Date();
                range = 12;
            } else {
                // Specific year
                const year = parseInt(value);
                startDate = new Date(year, 1, 1); // Jan 1
                endDate = new Date(year, 12, 31); // Dec 31
                range = 12;
            }
            
            return { startDate, endDate, range };
        }
        
        // Function to initialize/update heatmap
        window.initActivityHeatmap = function(filterValue = 'default') {
            if (typeof CalHeatmap === 'undefined') {
                console.error('CalHeatmap not loaded');
                return;
            }

            const data = window.examHeatmapData || [];
            const { startDate, endDate, range } = getDateRange(filterValue);

            // Destroy existing instance if it exists
            if (window.calInstance) {
                window.calInstance.destroy();
            }

            const cal = new CalHeatmap();
            window.calInstance = cal;

            cal.paint(
                {
                    itemSelector: '#activity-heatmap',
                    data: {
                        source: data,
                        type: 'json',
                        x: 'date',
                        y: 'value'
                    },
                    range: range,
                    date: {
                        start: startDate,
                        end: endDate,
                        step: 1,
                        unit: 'day',
                        highlight: [
                            new Date(),
                        ],
                        locale: {
                            weekStart: 1,
                        }    
                    },
                    domain: {
                        type: 'month',
                        gutter: 2
                    },
                    subDomain: {
                        type: 'day',
                        width: 20,
                        height: 20,
                        radius: 4
                    },
                    scale: {
                        color: {
                            type: 'linear',
                            domain: [0,2],
                            range: ['#fddbc7', '#b2182b'],
                        }
                    },
                },
                [
                    [Tooltip, 
                        {
                            enabled: true,
                            container: '#activity-heatmap-tooltip',
                            text: function(timestamp, value, dayjsDate) {
                                const date = dayjs(timestamp);
                                const formattedDate = date.format('( ddd ) MMM D, YYYY');
                                if (value === 1) {
                                    return formattedDate + ' - ' + ' 1 exam taken';
                                }
                                if (value === null || value === 0) {
                                    return formattedDate + ' - No exams taken';
                                }
                                return formattedDate + ' - ' + (value) + ' exams taken';
                            }
                        }
                    ],
                    [CalendarLabel,
                        {
                            position: 'left',
                            key: 'left',
                            text: () => ['Mon', '', '', 'Thu', '', '', 'Sun'],
                            textAlign: 'end',
                            width: 30,
                            padding: [0, 10, 0, 0],
                        },
                    ],
                ]
            );
        };
        
        // Populate dropdown on page load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                populateYearDropdown();
            });
        } else {
            populateYearDropdown();
        }
        
        // Add event listener for dropdown change
        document.addEventListener('DOMContentLoaded', function() {
            const yearFilter = document.getElementById('year-filter');
            if (yearFilter) {
                yearFilter.addEventListener('change', function(e) {
                    window.initActivityHeatmap(e.target.value);
                });
            }

            // AJAX privacy toggle for heatmap
            const hbtn = document.getElementById('heatmap-privacy-btn');
            if (hbtn) {
                hbtn.addEventListener('click', function() {
                    hbtn.disabled = true;
                    hbtn.style.opacity = '0.5';
                    fetch(
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
                        body: 'ajax_heatmap_toggle_privacy=1'
                    }).then(r => r.json()).then(data => {
                        if (data.success) {
                            const p = data.is_private;
                            hbtn.dataset.private = p ? '1' : '0';
                            hbtn.innerHTML = (p ? '&#128274; Private' : '&#128275; Public');
                        }
                    }).catch(() => {}).finally(() => { hbtn.disabled = false; hbtn.style.opacity = '1'; });
                });
            }
        });
        </script>
        ");
    }
}