<?php

if (!defined('QA_VERSION')) {
    header('Location: ../../');
    exit;
}

class qa_exam_stats_graph {

    public function allow_template($template)
    {
        return true;
    }

    public function allow_region($region)
    {
        return in_array($region, array('main', 'side', 'full'));
    }

    public function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
    {   
        $handle = qa_request_part(1); 
        $userid = qa_handle_to_userid($handle);
        $logged_in_userid = qa_get_logged_in_userid();
        $exam_count = qa_db_read_one_value(qa_db_query_sub(
            "SELECT COUNT(*) FROM ^exam_results WHERE userid = #",
            $userid
        ), true);

        if ($exam_count == 0) return;

        if ($userid != $logged_in_userid && (qa_get_logged_in_level() < QA_USER_LEVEL_SUPER)){
            // echo "<script>console.log('Exam Stats Graph Hidden: Other users stats cannot be viewed');</script>";
            return;
        }
        $data = self::get_stats_data($userid);

        echo '
        <div class="qa-exam-stats-container">
            <div class="qa-exam-stats-header">
                <div class="qa-exam-stats-title">Exam Statistics</div>
                <p class="qa-exam-stats-subtitle">Analyze your performance across different categories</p>
            </div>
            
            <div class="qa-exam-stats-controls">
                <label for="exam-stats-category" class="qa-exam-stats-label">View Statistics By:</label>
                <select id="exam-stats-category" class="qa-exam-stats-select">
                    <option value="difficulty" selected>Difficulty Level</option>
                    <option value="subject">Subject Area</option>
                    <option value="type">Question Type</option>
                    <option value="perf">Exam Performance (Overall)</option>
                    <optgroup label="Exam Performance by Accesslist" id="accesslist-group"></optgroup>
                </select>
            </div>
            
            <div class="qa-exam-stats-chart-wrapper">
                <canvas id="examStatsChart" class="qa-exam-stats-chart-canvas"></canvas>
            </div>
        </div>
        
        <script>
        
        (function() {
        
            const statsData = ';
            echo json_encode($data);
            echo ';

            let currentChart = null;

            function createPerformanceChart(accesslistFilter = null) {

                const canvas = document.getElementById("examStatsChart");
                if (!canvas) return;
                
                const context = canvas.getContext("2d");
                
                if (currentChart) {
                    currentChart.destroy();
                    currentChart = null;
                }
                
                // Reset canvas dimensions to clear any scaling issues
                const parent = canvas.parentElement;
                canvas.width = parent.offsetWidth;
                canvas.height = parent.offsetHeight;

                let data = statsData["perf"];

                // filtering by accesslist
                if (accesslistFilter !== null) {
                    const allowedIds = statsData.accesslist[accesslistFilter].examids;

                    // filter arrays by examids
                    const indexes = data.id
                        .map((id, idx) => allowedIds.includes(String(id)) ? idx : -1)
                        .filter(idx => idx !== -1);

                    data = {
                        id: indexes.map(i => data.id[i]),
                        date: indexes.map(i => data.date[i]),
                        labels: indexes.map((_, idx) => idx + 1),
                        exam_names: indexes.map(i => data.exam_names[i]),
                        user_accuracy: indexes.map(i => data.user_accuracy[i]),
                        topper_accuracy: indexes.map(i => data.topper_accuracy[i])
                    };
                }

                // console.log(data);
                // console.log(statsData.perf.exam_names);


                currentChart = new Chart(context, {
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                type: "line",
                                label: "Topper\'s Average Score (%)",
                                data: data.topper_accuracy,
                                borderColor: "rgba(245, 158, 11, 1)",
                                backgroundColor: "rgba(245, 158, 11, 0.2)",
                                borderWidth: 2,
                                fill: false,
                                tension: 0.3,
                                pointRadius: 4,
                                pointBackgroundColor: "rgba(245, 158, 11, 1)",
                                yAxisID: "y"
                            },
                            {
                                type: "bar",
                                label: "Your Score (%)",
                                data: data.user_accuracy,
                                backgroundColor: "rgba(59, 130, 246, 0.8)",
                                borderColor: "rgba(59, 130, 246, 1)",
                                borderWidth: 2,
                                borderRadius: 4,
                                borderSkipped: false,
                                yAxisID: "y",
                            },
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        onClick: (e) => {
                            const points = currentChart.getElementsAtEventForMode(
                                e, "index", 
                                { intersect: false }, 
                                true
                            );

                            if (points.length > 0) {
                                const index = points[0].index;
                                const id = data.id[index];

                                if (id) {
                                    const baseUrl = window.location.origin;
                                    window.open(`${baseUrl}/exam/${id}`, "_blank");
                                }
                            }
                        },
                        onHover: (event, activeElements) => {
                            event.native.target.style.cursor = "pointer";
                        },
                        plugins: {
                            legend: {
                                position: "top",
                                labels: {
                                    usePointStyle: false,
                                    padding: 10
                                }
                            },
                            title: {
                                display: true,
                                // text: "Exam-wise Accuracy Comparison",
                                color: "#111827",
                                font: {
                                    size: 14,
                                    weight: "600"
                                }
                            },
                            tooltip: {
                                backgroundColor: "rgba(17, 24, 39, 0.95)",
                                padding: 8,
                                borderColor: "rgba(75, 85, 99, 0.5)",
                                borderWidth: 1,
                                mode: "index",          // <-- important: show all datasets at that index
                                intersect: false,       // <-- ensures tooltip appears even if cursor is between points
                                callbacks: {
                                    title: function(tooltipItems) {
                                        const index = tooltipItems[0].dataIndex;
                                        const xLabel = data.labels[index];
                                        const examName = data.exam_names[index];
                                        const examDate = data.date[index].split(" ")[0];
                                        return "Exam Number: " + xLabel + "\n" 
                                        // + "Exam ID: " + data.id[index] + "\n" 
                                        + "Exam Name: " + examName + "\n" 
                                        + "Date Taken: " + examDate;
                                    },
                                    label: function(context) {
                                        const index = context.dataIndex;
                                        // const chartData = context.chart.data.datasets[context.datasetIndex].data;

                                        if (context.dataset.label.includes("Your")) {
                                            return `You: ${data.user_accuracy[index]}%`;
                                        } else if (context.dataset.label.includes("Topper")) {
                                            return `Topper: ${data.topper_accuracy[index]}%`;
                                        }

                                        return context.dataset.label + ": " + context.parsed.y + "%";
                                    }
                                }
                            }
                                

                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                title: {
                                    display: true,
                                    // text: "Accuracy (%)"
                                },
                                ticks: {
                                    stepSize: 20,
                                    color: "#6b7280"
                                },
                                grid: {
                                    color: "rgba(229, 231, 235, 0.8)",
                                    drawBorder: false
                                }
                            },
                            x: {
                                ticks: {
                                    color: "#6b7280"
                                },
                                grid: {
                                    display: false,
                                    drawBorder: false
                                }
                            }
                        }
                    }
                });
            }
            
            function createChart(category) {
                const canvas = document.getElementById("examStatsChart");
                if (!canvas) return;
                
                const context = canvas.getContext("2d");
                
                if (currentChart) {
                    currentChart.destroy();
                    currentChart = null;
                }
                
                // Reset canvas dimensions to clear any scaling issues
                const parent = canvas.parentElement;
                canvas.width = parent.offsetWidth;
                canvas.height = parent.offsetHeight;
                
                const data = statsData[category];
                const maxValue = Math.max(...data.attempted, ...data.skipped);
                
                // 4 intervals for all graph
                let step = Math.ceil(maxValue / 4);
                
                const attempted = data.attempted;
                const correct = data.correct;
                const incorrect = attempted.map((a, i) => a - correct[i]);

                currentChart = new Chart(context, {
                    type: "bar",
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: "Correct",
                                data: correct,
                                backgroundColor: "rgba(34, 197, 94, 0.8)",
                                borderColor: "rgba(34, 197, 94, 1)",
                                borderWidth: 2,
                                borderRadius: 4,
                                borderSkipped: false,
                                stack: "attemptStack"
                            },
                            {
                                label: "Incorrect",
                                data: incorrect,
                                backgroundColor: "rgba(239, 68, 68, 0.8)",
                                borderColor: "rgba(239, 68, 68, 1)",
                                borderWidth: 2,
                                borderRadius: 4,
                                borderSkipped: false,
                                stack: "attemptStack"
                            },
                            {
                                label: "Skipped",
                                data: data.skipped,
                                backgroundColor: "rgba(245, 158, 11, 0.8)",
                                borderColor: "rgba(245, 158, 11, 1)",
                                borderWidth: 2,
                                borderRadius: 4,
                                borderSkipped: false,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: "index",
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: "top",
                                labels: {
                                    padding: 10,
                                    font: {
                                        size: 11,
                                        weight: "500"
                                    },
                                    usePointStyle: true,
                                    pointStyle: "rectRounded"
                                }
                            },
                            tooltip: {
                                backgroundColor: "rgba(17, 24, 39, 0.95)",
                                padding: 8,
                                titleFont: {
                                    size: 13,
                                    weight: "500"
                                },
                                bodyFont: {
                                    size: 12
                                },
                                borderColor: "rgba(75, 85, 99, 0.5)",
                                borderWidth: 1,
                                cornerRadius: 5,
                                displayColors: true,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || "";
                                        if (label) {
                                            label += ": ";
                                        }
                                        label += context.parsed.y + " questions";
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: {
                                    stepSize: step,
                                    font: {
                                        size: 10
                                    },
                                    color: "#6b7280"
                                },
                                grid: {
                                    color: "rgba(229, 231, 235, 0.8)",
                                    //check dark mode

                                    // color: "rgba(75, 85, 99, 0.8)",
                                    drawBorder: false
                                }
                            },
                            x: {
                                stacked: true,
                                ticks: {
                                    font: {
                                        size: 10
                                    },
                                    color: "#6b7280"
                                },
                                grid: {
                                    display: false,
                                    drawBorder: false
                                }
                            }
                        }
                    }
                });
            }
           
            
            window.addEventListener("DOMContentLoaded", () => {
                const select = document.getElementById("exam-stats-category");

                // Force dropdown to show Difficulty
                select.value = "difficulty";
                requestAnimationFrame(() => createChart("difficulty"));
            });    
            document.addEventListener("DOMContentLoaded", function () {
                const allowedDomains = ["gateoverflow.in", "surabi.gateoverflow.in", "db.gateoverflow.in"];
                const hostname = window.location.hostname;
            
                if (!allowedDomains.includes(hostname)) {
                    const subjectOption = document.querySelector("#exam-stats-category option[value=\"subject\"]");
                    if (subjectOption) subjectOption.remove();
                }
            });
            
            const accesslistGroup = document.getElementById("accesslist-group");

            Object.keys(statsData.accesslist).forEach(key => {
                const item = statsData.accesslist[key];
                const option = document.createElement("option");
                option.value = "perf_" + key;
                option.textContent = item.label + ` (${item.count})`;
                accesslistGroup.appendChild(option);
            });

                
            // Update chart when category changes
            const categorySelect = document.getElementById("exam-stats-category");
            categorySelect.addEventListener("change", function (e) {
                const value = e.target.value;
                if (value === "perf") {
                    createPerformanceChart(); // Overall performance
                }
                else if (value.startsWith("perf_")) {
                    const acc = value.replace("perf_", "");
                    createPerformanceChart(acc); // filtered
                }
                else {
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            createChart(e.target.value);
                        });
                    });
                }
            });
                    
        })();
        </script>';
    }

    
    public function init_queries($tableslc) {
        return null;
    }
      
    public static function get_stats_data($userid) {
        // require_once('/var/www/html/qa/qa-plugin/exam-creator/db/selects.php');
        
        //Labels for chart
        $subject_labels = array(
            // 'Total',
            
            // Aptitude
            'A. Aptitude',
            'G. Aptitude',
            'Q. Aptitude',
            'V. Aptitude',
            'S. Aptitude',

            // Engg Maths (Calculus, Probability, Linear Algebra)
            'EM',
            // Discrete Maths, Combinatorics, Logic, Graph Theory
            'DM',

            // Core CS
            'DL',
            'COA',
            'C & DS',
            'Algorithms',
            'TOC',
            'CD',
            'OS',
            'Databses',
            'CN',

            //DA
            'AI',
            'ML',
            'Python',
            
            'Other'
        );
        $difficulty_labels = array('Total', 'Easy', 'Medium', 'Hard', '1 Mark', '2 Marks');
        $type_labels = array('Total', 'NAT', 'MCQ', 'MSQ');
        
        $subject_map = [
            'analytical aptitude'      => 'A. Aptitude',
            'general aptitude'         => 'G. Aptitude',
            'quantitative aptitude'    => 'Q. Aptitude',
            'verbal aptitude'          => 'V. Aptitude',
            'spatial aptitude'         => 'S. Aptitude',

            'calculus'                 => 'EM',
            'probability'              => 'EM',
            'linear algebra'           => 'EM',
            'discrete mathematics'     => 'DM',
            'set theory & algebra'     => 'DM',
            'combinatory'              => 'DM',
            'graph theory'             => 'DM',
            'mathematical logic'       => 'DM',

            'digital logic'            => 'DL',
            'co and architecture'      => 'COA',
            'computer networks'        => 'CN',
            'programming in c'         => 'C & DS',
            'ds'                       => 'C & DS',

            'algorithms'               => 'Algorithms',
            'theory of computation'    => 'TOC',
            'compiler design'          => 'CD',
            'operating system'         => 'OS',
            'databases'                => 'Databses',

            'artificial intelligence'  => 'AI',
            'machine learning'         => 'ML',
            'programming in python'    => 'Python',
        ];

        $type_map = [
            'numerical-answers'  => 'NAT',
            'msq'                => 'MSQ',
            'multiple-selects'   => 'MSQ',
            // 'mcq'                => 'MCQ',
        ];

        $difficulty_stats = array_fill_keys($difficulty_labels, ['attempted' => 0, 'correct' => 0, 'skipped' => 0]);
        $subject_stats = array_fill_keys($subject_labels, ['attempted' => 0, 'correct' => 0, 'skipped' => 0]);
        $type_stats = array_fill_keys($type_labels, ['attempted' => 0, 'correct' => 0, 'skipped' => 0]);

        $exam_results = qa_db_read_all_assoc(qa_db_query_sub(
            "SELECT * 
                FROM ^exam_results 
                WHERE userid = # 
                ORDER BY datetime ASC",
                $userid
        ));
        //for perf array 
        $exam_user_percentage = array();
        $exam_avg_topper_percentage = array();
        $exam_name = array();
        $exam_ids = array();
        $exam_labels = array();
        $accesslist_data = array();
        $date_array = array();

        $exam_marks = array();
        $category_dict = array();
        foreach ($exam_results as $result) {
            $response_table = json_decode(stripslashes($result['responsestring']), true);
            $examid = $result['examid'];
            $resultid = $result['resultid'];
            $exam_info = RetrieveExamInfo_db($examid, "var");
            $exam_row = qa_db_read_one_assoc(
                qa_db_query_sub(
                    "SELECT accesslists 
                    FROM ^exams 
                    WHERE postid = #",
                    $examid
                ),
                true
            );
            $date_array[$resultid] = $result['datetime'];

            require_once('/var/www/html/qa/qa-plugin/exam-creator/qa-ec-view-accesslist.php');
            $accesslist_ids_raw = qa_db_usermeta_get($userid, 'accesslists');

            $accesslist_ids = array_filter(
                array_map('trim', explode(',', $accesslist_ids_raw))
            );
            // echo '
            // <script>
            //     console.log("User Accesslists: ", ' .json_encode($accesslist_ids). ');
            // </script>';
            $accesslist_names_map = [];

            if (!empty($accesslist_ids)) {
                $placeholders = implode(',', array_fill(0, count($accesslist_ids), '#'));

                $query = "
                    SELECT listid, name
                    FROM ^accesslists
                    WHERE listid IN ($placeholders)
                ";

                $rows = qa_db_query_sub($query, ...$accesslist_ids);

                while ($row = qa_db_read_one_assoc($rows, true)) {
                    $accesslist_names_map[(string)$row['listid']] = $row['name'];
                }
            }
            $accesslist_names_map['(none)'] = 'Open Exams';
            // echo '
            // <script>
            //     console.log("User Accesslists:", ' . json_encode($accesslist_ids) . ');
            //     console.log("Accesslist Names Map:", ' . json_encode($accesslist_names_map) . ');
            // </script>';


            $acc_raw = $exam_row['accesslists'];

            if ($acc_raw === null || trim($acc_raw) === "") {
                $acc_list = ["(none)"];
            } else {
                $acc_list = array_map('trim', explode(',', $acc_raw));
            }

            foreach ($acc_list as $acc) {
                //skip accesslists not present in map
                if (!isset($accesslist_names_map[$acc])) {
                    continue;
                }
                if (!isset($accesslist_data[$acc])) {
                    $accesslist_data[$acc] = [
                        'count' => 0,
                        'examids' => [],
                        'name' => [],
                        'label' => isset($accesslist_names_map[$acc]) ? $accesslist_names_map[$acc] : "Accesslist $acc"
                    ];
                }

                $accesslist_data[$acc]['count']++;
                $accesslist_data[$acc]['examids'][] = $examid;
                $accesslist_data[$acc]['name'][] = $exam_info['name'];
            }

            // if($exam_info['total_qs'] >= 30){   //show all exams for now
                $exam_no = count($exam_labels) + 1;
                array_push($exam_ids, $examid);
                array_push($exam_labels, $exam_no);
                array_push($exam_name, $exam_info['name']);
                $user_marks = $result['marks'];
                $total_marks = $exam_info['total_marks'];
                $user_percentage = ($total_marks > 0) ? ($user_marks / $total_marks) * 100 : 0;
                if ($user_percentage < 0) $user_percentage = 0; //prevent negative percentages
                array_push($exam_user_percentage, round($user_percentage,2));

                $totaltime = $exam_info['duration'];
                $total_exam_attempts = get_exam_attempts($examid);
                $limit = max(1, round(0.1 * $total_exam_attempts));
                $spec = qa_exam_db_examtoppers_selectspec($examid, $totaltime, $limit);
                $toppers = qa_db_select_with_pending($spec);

                $sum_marks = 0;
                $count = 0;

                foreach ($toppers as $uid => $row) {
                    $sum_marks += floatval($row['marks']);
                    $count++;
                }

                $top_avg_marks = ($count > 0) ? $sum_marks / $count : 0;
                $top_avg_accuracy = ($total_marks > 0) ? ($top_avg_marks / $total_marks) * 100 : 0;
                if ($top_avg_accuracy > 99) $top_avg_accuracy = 99; //prevent 100%
                array_push($exam_avg_topper_percentage, round($top_avg_accuracy, 2));
            // }

            if (!$exam_info || empty($exam_info['section'])) continue;
            $section_array=$exam_info["section"];

            for($i=0; $i<sizeOf($section_array);$i++)
            {
                $response_status_table_part=array();
                $category_array_part=array();

                $section_name = $section_array[$i]["name"];
                $qs_array= $section_array[$i]["question"];
                for($j=0; $j<sizeOf($qs_array); $j++)
                {
                    $postid = $qs_array[$j]["post_id"];
                    $qtype = $qs_array[$j]['type'];
                    $tags = array_map('trim', explode(',', $qs_array[$j]['tags']));
                    $category = $qs_array[$j]['category'];
                    $category_dict[$category] += 1;
                    $responses = json_decode(stripslashes($result['responsestring']), true);
                    $user_response = $response_table[$postid];

                    $correct_answers = array();
                    $ca = $qs_array[$j]["answer"];
                    $caa = explode(",", $ca);
                    for($jj = 0; $jj < count($caa); $jj++) {
                        $c = $caa[$jj];
                        $correct_answers[$jj] = array();
                        $panswers= explode(";", $c);
                        foreach($panswers as $panswer)
                        {
                            $correct_answers[$jj][]=mapalphabettodigit(trim(strtolower($panswer)));
                        }
                    }
                    $status = calc_response_status($correct_answers, $user_response, $qtype);
                    // echo "**************************************Response Status: $status ***********************************<br>";
                    // 0: Not Attempted, 
                    // 1: Correct, 
                    // 2: Incorrect, 
                    // 3: Marks to All

                    $isAttempted = ($status != 0);
                    $isCorrect   = ($status == 1 || $status == 3);
                    $isSkipped   = ($status == 0);
                    
                    //Subject Area
                    $category_lower = strtolower(trim($qs_array[$j]['category']));
                    $mapped = $subject_map[$category_lower] ?? 'Other';
                    self::update_stat($subject_stats[$mapped], $isAttempted, $isCorrect, $isSkipped);

                    //Type
                    $question_type = 'MCQ'; // default

                    foreach ($tags as $tag) {
                        $tag_lower = strtolower($tag);
                        if (isset($type_map[$tag_lower])) {
                            $question_type = $type_map[$tag_lower];
                            break;
                        }
                    }
                    self::update_stat($type_stats[$question_type], $isAttempted, $isCorrect, $isSkipped);

                    //Difficulty
                    $difficulty_found = false;
                    $question_difficulty = null;
                    $marks_difficulty = null;

                    //Difficulty-based tags
                    foreach ($tags as $tag) {
                        $tag_lower = strtolower(trim($tag));

                        // Exact match for easy / difficult
                        if ($tag_lower === 'easy') {
                            $question_difficulty = 'Easy';
                            $difficulty_found = true;
                            break;
                        }
                        if ($tag_lower === 'difficult' || $tag_lower === 'hard') {
                            $question_difficulty = 'Hard';
                            $difficulty_found = true;
                            break;
                        }
                    }
                    if (!$difficulty_found) {
                        $question_difficulty = 'Medium';
                    }
                    self::update_stat($difficulty_stats[$question_difficulty], $isAttempted, $isCorrect, $isSkipped);

                    //Mark based tags
                    foreach ($tags as $tag) {
                        $tag_lower = strtolower(trim($tag));

                        if (strpos($tag_lower, 'one-mark') !== false) {
                            $marks_difficulty = '1 Mark';
                            $marks_found = true;
                            break;
                        }
                        if (strpos($tag_lower, 'two-marks') !== false) {
                            $marks_difficulty = '2 Marks';
                            $marks_found = true;
                            break;
                        }
                    }
                    if (!$marks_found) {
                        $marks_difficulty = '1 Mark';
                    }
                    self::update_stat($difficulty_stats[$marks_difficulty], $isAttempted, $isCorrect, $isSkipped);

                    self::update_stat($difficulty_stats['Total'], $isAttempted, $isCorrect, $isSkipped);
                    // self::update_stat($subject_stats['Total'], $isAttempted, $isCorrect, $isSkipped);
                    self::update_stat($type_stats['Total'], $isAttempted, $isCorrect, $isSkipped);
                }
            }
        }

        $performance_data = array(
            'id' => array_values($exam_ids),
            'date' => array_values($date_array),
            'labels' => array_values($exam_labels),
            'exam_names' => array_values($exam_name),
            'user_accuracy' => array_values($exam_user_percentage),
            'topper_accuracy' => array_values($exam_avg_topper_percentage)
        );
        // echo '<script> console.log('.json_encode($category_dict).') </script>';

        // echo '<script> console.log('.json_encode($accesslist_data).') </script>';
        $accesslist_filtered = [];
        // only include accesslists that user has exams in
        foreach ($accesslist_data as $key => $d) {
            if ($d['count'] > 0) {
                $accesslist_filtered[$key] = $d;
            }
        }

        return array(
            'difficulty' => array(
                'labels' => $difficulty_labels,
                'attempted' => array_map(fn($l) => $difficulty_stats[$l]['attempted'], $difficulty_labels),
                'correct'   => array_map(fn($l) => $difficulty_stats[$l]['correct'], $difficulty_labels),
                'skipped'   => array_map(fn($l) => $difficulty_stats[$l]['skipped'], $difficulty_labels),
            ),
            'subject' => array(
                'labels' => $subject_labels,
                'attempted' => array_map(fn($l) => $subject_stats[$l]['attempted'], $subject_labels),
                'correct'   => array_map(fn($l) => $subject_stats[$l]['correct'], $subject_labels),
                'skipped'   => array_map(fn($l) => $subject_stats[$l]['skipped'], $subject_labels),
            ),
            'type' => array(
                'labels' => $type_labels,
                'attempted' => array_map(fn($l) => $type_stats[$l]['attempted'], $type_labels),
                'correct'   => array_map(fn($l) => $type_stats[$l]['correct'], $type_labels),
                'skipped'   => array_map(fn($l) => $type_stats[$l]['skipped'], $type_labels),
            ),
            'perf' => $performance_data,
            'accesslist' => $accesslist_filtered,
        );
    }

    private static function update_stat(&$stat, $isAttempted, $isCorrect, $isSkipped) {
        if ($isAttempted) $stat['attempted']++;
        if ($isCorrect) $stat['correct']++;
        if ($isSkipped) $stat['skipped']++;
    }
}
