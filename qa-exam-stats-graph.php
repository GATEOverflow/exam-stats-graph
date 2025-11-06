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
        $exam_count = qa_db_read_one_value(qa_db_query_sub(
            "SELECT COUNT(*) FROM ^exam_results WHERE userid = #",
            $userid
        ), true);

        if ($exam_count == 0) return;

        $data = self::get_stats_data($userid);

        echo '
        <div class="qa-exam-stats-container">
            <div class="qa-exam-stats-header">
                <h2 class="qa-exam-stats-title">Exam Statistics</h2>
                <p class="qa-exam-stats-subtitle">Analyze your performance across different categories</p>
            </div>
            
            <div class="qa-exam-stats-controls">
                <label for="exam-stats-category" class="qa-exam-stats-label">View Statistics By:</label>
                <select id="exam-stats-category" class="qa-exam-stats-select">
                    <option value="difficulty">Difficulty Level</option>
                    <option value="subject">Subject Area</option>
                    <option value="type">Question Type</option>
                    <option value="perf">Exam Performance</option>
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

            function createPerformanceChart() {
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

                const data = statsData["perf"];
                // console.log(data);


                currentChart = new Chart(context, {
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                type: "line",
                                label: "Topper\'s Average Accuracy (%)",
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
                                label: "Your Accuracy (%)",
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
                        responsive: false,
                        maintainAspectRatio: false,
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
                                        return statsData.perf.exam_names[index];
                                    },
                                    label: function(context) {
                                        const index = context.dataIndex;
                                        const data = statsData.perf;

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
                const maxValue = Math.max(...data.attempted, ...data.correct, ...data.skipped);
                temp = Math.ceil(maxValue / 3);
                const step = temp;
                
                currentChart = new Chart(context, {
                    type: "bar",
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: "Total Attempted",
                                data: data.attempted,
                                backgroundColor: "rgba(59, 130, 246, 0.8)",
                                borderColor: "rgba(59, 130, 246, 1)",
                                borderWidth: 2,
                                borderRadius: 4,
                                borderSkipped: false,
                            },
                            {
                                label: "Correct Attempts",
                                data: data.correct,
                                backgroundColor: "rgba(34, 197, 94, 0.8)",
                                borderColor: "rgba(34, 197, 94, 1)",
                                borderWidth: 2,
                                borderRadius: 4,
                                borderSkipped: false,
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
                        responsive: false,
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
                    
            // Initialize chart with default category
            window.addEventListener("DOMContentLoaded", () => {
                requestAnimationFrame(() => createChart("difficulty"));
            });
                
            // Update chart when category changes
            const categorySelect = document.getElementById("exam-stats-category");
            categorySelect.addEventListener("change", function (e) {
                const value = e.target.value;
                if (value === "perf") {
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            createPerformanceChart();
                        });
                    });
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
        // require_once('/var/www/html/qa/qa-plugin/exam-creator/db/selects.php');s

        $difficulty_labels = array('Total', 'Easy', 'Hard', '1 Mark', '2 Marks');
        $subject_labels = array('Total', 'Aptitude', 'Mathematics', 'DL', 'COA', 'C & DS', 'Algorithms', 'TOC', 'CD', 'OS', 'Databses', 'CN', 'General', 'Other');
        $type_labels = array('Total', 'NAT', 'MCQ', 'MSQ');

        $difficulty_stats = array_fill_keys($difficulty_labels, ['attempted' => 0, 'correct' => 0, 'skipped' => 0]);
        $subject_stats = array_fill_keys($subject_labels, ['attempted' => 0, 'correct' => 0, 'skipped' => 0]);
        $type_stats = array_fill_keys($type_labels, ['attempted' => 0, 'correct' => 0, 'skipped' => 0]);

        $exam_results = qa_db_read_all_assoc(qa_db_query_sub(
            "SELECT * 
                FROM ^exam_results 
                WHERE userid = # 
                ORDER BY datetime ASC",
                $userid
            )
        );

        //for perf array 
        $exam_user_percentage = array();
        $exam_avg_topper_percentage = array();
        $exam_name = array();
        $exam_id = array();

        $exam_marks = array();
        foreach ($exam_results as $result) {

            $response_table = json_decode(stripslashes($result['responsestring']), true);
            $examid = $result['examid'];
            $exam_info = RetrieveExamInfo_db($examid, "var");
            
            if($total_questions >= 30){   
                $exam_string = 'ExamID ' . $examid;
                array_push($exam_id, $exam_string);
                array_push($exam_name, $exam_info['name']);
                $user_marks = $result['marks'];
                $total_marks = $exam_info['total_marks'];
                $total_questions = $exam_info['total_qs'];
                $user_percentage = ($total_marks > 0) ? ($user_marks / $total_marks) * 100 : 0;
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
                array_push($exam_avg_topper_percentage, round($top_avg_accuracy, 2));
            }

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
                    $responses = json_decode(stripslashes($result['responsestring']), true);
                    $user_response = json_decode($responses[$postid], true);

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
                    
                    $cat_map = [
                        'aptitude'              => 'Aptitude',
                        // 'discrete mathematics'  => 'DM',
                        'mathematics'           => 'Mathematics',
                        'operating-system'      => 'OS',
                        'compiler-design'       => 'Compilers',
                        'databases'             => 'Databases',
                        'data-structures'       => 'C & DS',
                        'theory-of-computation' => 'TOC',
                        'digital-logic'         => 'DL',
                        // 'engineering-mathematics' => 'EM',
                        'co-and-architecture'   => 'COA',
                        'computer-networks'     => 'CN',
                        'general'               => 'General'
                    ];

                    $difficulty_map = [
                        'easy'    => 'Easy',
                        'difficult'    => 'Hard',
                        'one-mark'    => '1 Mark',
                        'two-marks'   => '2 Marks',
                    ];

                    $type_map = [
                        'numerical-answers'  => 'NAT',
                        'msq'                => 'MSQ',
                        'multiple-selects'   => 'MSQ',
                        // 'mcq'                => 'MCQ',
                    ];
                    
                    //Subject Area
                    $category_lower = strtolower(trim($qs_array[$j]['category']));
                    if (isset($cat_map[$category_lower])) {
                        self::update_stat($subject_stats[$cat_map[$category_lower]], $isAttempted, $isCorrect, $isSkipped);
                    } else {
                        self::update_stat($subject_stats['Other'], $isAttempted, $isCorrect, $isSkipped);
                    }

                    //Type
                    foreach($tags as $tag) {
                        $tag_lower = strtolower($tag);
                        if (isset($type_map[$tag_lower])) {
                            $mcq = 0;
                            self::update_stat($type_stats[$type_map[$tag_lower]], $isAttempted, $isCorrect, $isSkipped);
                        }
                        else{
                            self::update_stat($type_stats['MCQ'], $isAttempted, $isCorrect, $isSkipped);
                        }
                    }
                    
                    //Difficulty
                    foreach($tags as $tag) {
                        $tag_lower = strtolower($tag);
                        if (isset($difficulty_map[$tag_lower])) {
                            self::update_stat($difficulty_stats[$difficulty_map[$tag_lower]], $isAttempted, $isCorrect, $isSkipped);
                        }
                    }

                    // Update total counts
                    self::update_stat($difficulty_stats['Total'], $isAttempted, $isCorrect, $isSkipped);
                    self::update_stat($subject_stats['Total'], $isAttempted, $isCorrect, $isSkipped);
                    self::update_stat($type_stats['Total'], $isAttempted, $isCorrect, $isSkipped);
                }
            }
        }
        $performance_data = array(
            'labels' => array_values($exam_id),
            'exam_names' => array_values($exam_name),
            'user_accuracy' => array_values($exam_user_percentage),
            'topper_accuracy' => array_values($exam_avg_topper_percentage)
        );

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
        );
    }

    private static function update_stat(&$stat, $isAttempted, $isCorrect, $isSkipped) {
        if ($isAttempted) $stat['attempted']++;
        if ($isCorrect) $stat['correct']++;
        if ($isSkipped) $stat['skipped']++;
    }
}
