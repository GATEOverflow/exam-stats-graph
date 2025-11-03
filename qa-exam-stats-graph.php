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
        $data = self::get_stats_data();

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
        
            const statsData = ' . json_encode($data) . ';

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
                console.log(data);


                currentChart = new Chart(context, {
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                type: "bar",
                                label: "Your Accuracy (%)",
                                data: data.user_accuracy,
                                backgroundColor: "rgba(59, 130, 246, 0.8)", // blue bar
                                borderColor: "rgba(59, 130, 246, 1)",
                                borderWidth: 2,
                                borderRadius: 4,
                                borderSkipped: false,
                                yAxisID: "y",
                            },
                            {
                                type: "line",
                                label: "Topper\'s Average Accuracy (%)",
                                data: data.topper_accuracy,
                                borderColor: "rgba(245, 158, 11, 1)", // yellow line
                                backgroundColor: "rgba(245, 158, 11, 0.2)",
                                borderWidth: 2,
                                fill: false,
                                tension: 0.3,
                                pointRadius: 4,
                                pointBackgroundColor: "rgba(245, 158, 11, 1)",
                                yAxisID: "y"
                            }
                        ]
                    },
                    options: {
                        responsive: false,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: "top",
                                labels: {
                                    usePointStyle: true,
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
                                    label: function(context) {
                                        const index = context.dataIndex;

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
                const step = Math.ceil(maxValue / 5);

                if(category === "perf") {
                    currentChart = new Chart(context, {
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                type: "bar",
                                label: "Your Accuracy (%)",
                                data: data.user_accuracy,
                                backgroundColor: "rgba(59, 130, 246, 0.8)", // blue bar
                                borderColor: "rgba(59, 130, 246, 1)",
                                borderWidth: 2,
                                borderRadius: 4,
                                borderSkipped: false,
                                yAxisID: "y",
                            },
                            {
                                type: "line",
                                label: "Topper\'s Average Accuracy (%)",
                                data: data.topper_accuracy,
                                borderColor: "rgba(245, 158, 11, 1)", // yellow line
                                backgroundColor: "rgba(245, 158, 11, 0.2)",
                                borderWidth: 2,
                                fill: false,
                                tension: 0.3,
                                pointRadius: 4,
                                pointBackgroundColor: "rgba(245, 158, 11, 1)",
                                yAxisID: "y"
                            }
                        ]
                    },
                    options: {
                        responsive: false,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: "top",
                                labels: {
                                    usePointStyle: true,
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
                                    label: function(context) {
                                        const index = context.dataIndex;

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
                
                else{
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
            }
                    
            // Initialize chart with default category
            window.addEventListener("load", function() {
                setTimeout(() => {
                    createChart("difficulty");
                }, 200);
            });
                
            const categorySelect = document.getElementById("exam-stats-category");
            categorySelect.addEventListener("change", function (e) {
                const value = e.target.value;
                if (value === "perf") {
                    createPerformanceChart();
                }
                else {
                    createChart(e.target.value);
                }
            });
            
            const observer = new MutationObserver(() => {
                createChart("difficulty");
            });
                    
        })();
        </script>';
    }

    
    public function init_queries($tableslc) {
        return null;
    }
    
    public static function get_sample_stats_data() {
        // Sample data
        return array(
            'difficulty' => array(
                'labels' => array('Total', 'Easy', 'Medium', 'Hard', '1 Mark', '2 Marks'),
                'attempted' => array(150, 45, 50, 35, 60, 40),
                'correct' => array(100, 38, 32, 18, 48, 32),
                'skipped' => array(20, 5, 8, 5, 7, 3)
            ),
            'subject' => array(
                'labels' => array('Total', 'Aptitude', 'OS', 'Compiler', 'DBMS', 'DS'),
                'attempted' => array(150, 25, 30, 28, 35, 32),
                'correct' => array(100, 20, 22, 18, 24, 26),
                'skipped' => array(20, 3, 4, 5, 4, 4)
            ),
            'type' => array(
                'labels' => array('Total', 'NAT', 'mcq_flag', 'MSQ'),
                'attempted' => array(150, 40, 70, 40),
                'correct' => array(100, 28, 50, 22),
                'skipped' => array(20, 6, 8, 6)
            )
        );
    }

    public static function get_stats_data() {
        require_once('/var/www/html/qa/qa-plugin/exam-creator/db/selects.php');
        $handle = qa_request_part(1); 
        $userid = qa_handle_to_userid($handle);
        // $userid = qa_get_logged_in_userid();
        // echo "User ID: $userid<br>";
        if (!$userid) {
            return array(); // user not logged in
        }

        // Predefine label sets
        $difficulty_labels = array('Total', 'Easy', 'Medium', 'Hard', '1 Mark', '2 Marks');
        $subject_labels = array('Total', 'Aptitude', 'OS', 'Compiler', 'DBMS', 'DS', 'Discrete-math');
        $type_labels = array('Total', 'NAT', 'MCQ', 'MSQ');

        // Initialize counters
        $difficulty_stats = array_fill_keys($difficulty_labels, ['attempted' => 0, 'correct' => 0, 'skipped' => 0]);
        $subject_stats = array_fill_keys($subject_labels, ['attempted' => 0, 'correct' => 0, 'skipped' => 0]);
        $type_stats = array_fill_keys($type_labels, ['attempted' => 0, 'correct' => 0, 'skipped' => 0]);

        // fetch user’s all exam attempts
        $exam_results = qa_db_read_all_assoc(qa_db_query_sub(
            // "SELECT DISTINCT * FROM ^exam_results WHERE userid = #",
            "SELECT *
                FROM (
                    SELECT *,
                        ROW_NUMBER() OVER (PARTITION BY examid ORDER BY datetime ASC) as rn
                    FROM ^exam_results
                    WHERE userid = #
                ) AS ordered_attempts
                WHERE rn = 1;
            ",
            $userid
        ));

        foreach ($exam_results as $result) {
            // $response_table=array();
            // $response_table = json_decode($response, true);
            $response_table = json_decode(stripslashes($result['responsestring']), true);
            // echo "Exam Responses: ".json_encode($response_table)."<br>";
            // echo "Exam Responses 167: ".json_encode($response_table[167])."<br>";
            $examid = $result['examid'];
            $responses = json_decode($result['responsestring'], true);
            // if (!$responses) continue;
            // echo "Exam Responses: ".json_encode($responses)."<br>";
            $exam_info = RetrieveExamInfo_db($examid, "var");
            // echo "exit retrieve Exam ID: $examid<br>";
            // echo "Exam ID: $examid<br>";
            if (!$exam_info || empty($exam_info['section'])) continue;
            $section_array=$exam_info["section"];

            // foreach ($exam_info['section'] as $section) {
            //     foreach ($section['question'] as $question) {
            for($i=0; $i<sizeOf($section_array);$i++)
            {
                /*Section Name*/
                $response_status_table_part=array();

                /* category part */
                $category_array_part=array();

                $section_name = $section_array[$i]["name"];
                $qs_array= $section_array[$i]["question"];
                for($j=0; $j<sizeOf($qs_array); $j++)
                {
                    $postid = $qs_array[$j]["post_id"];
                    // $qtype = $question['type']; // "multiple choice", "numerical", etc.
                    $qtype = $qs_array[$j]['type'];
                    // $all_tags = explode(",", $qs_array[$j]["tags"]);
                    
                    // $tags_array[$postid] = implode(",", $all_tags);
                    // $tags = array_map('trim', explode(',', $question['tags']));
                    $tags = array_map('trim', explode(',', $qs_array[$j]['tags']));
                    // echo " response fetching for post id: $postid <br>";
                    // echo "response : " .json_decode($result['responsestring'], true). "<br>";
                    $responses = json_decode($result['responsestring'], true);
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

                    // echo "Post ID: $postid, User Response: ".json_encode($user_response). "Question Type: $qtype<br>";
                    // echo "Correct Answers: ".json_encode($correct_answers)."<br>";
                    $status = calc_response_status($correct_answers, $user_response, $qtype);
                    // echo "**************************************Response Status: $status ***********************************<br>";
                    // 0: Not Attempted, 
                    // 1: Correct, 
                    // 2: Incorrect, 
                    // 3: Marks to All

                    $isAttempted = ($status != 0);
                    $isCorrect   = ($status == 1 || $status == 3);
                    // echo "isAttempted: $isAttempted, isCorrect: $isCorrect, Response Status: $status<br>";
                    $isSkipped   = ($status == 0);

                    // Update difficulty stats
                    foreach ($tags as $tag) {
                        // echo "Tag: $tag<br>";
                        $tag_lower = strtolower($tag);

                        // Difficulty-based tags
                        if (in_array($tag_lower, ['easy', 'medium', 'hard'])) {
                            self::update_stat($difficulty_stats[$tag_lower == 'easy' ? 'Easy' : ucfirst($tag_lower)], $isAttempted, $isCorrect, $isSkipped);
                        }

                        // Marks-based tags
                        if (strpos($tag_lower, 'one-mark') !== false) {
                            self::update_stat($difficulty_stats['1 Mark'], $isAttempted, $isCorrect, $isSkipped);
                        } elseif (strpos($tag_lower, 'two-marks') !== false) {
                            self::update_stat($difficulty_stats['2 Marks'], $isAttempted, $isCorrect, $isSkipped);
                        }

                        // Subject-based tags
                        if (in_array($tag_lower, ['aptitude', 'os', 'compiler', 'dbms', 'ds', 'discrete-math'])) {
                            self::update_stat($subject_stats[ucfirst($tag_lower)], $isAttempted, $isCorrect, $isSkipped);
                        }

                        // Type-based tags
                        $mcq_flag = 1;
                        if (in_array($tag_lower, ['nat', 'mcq_flag', 'msq', 'numerical-answers','multiple-selects'])) {
                            $mappedType = 'MCQ'; 
                            if (strpos($tag_lower, 'nat') !== false || strpos($tag_lower, 'numerical-answers') !== false) {
                                $mcq_flag =0;
                                $mappedType = 'NAT';
                            } elseif (strpos($tag_lower, 'msq') !== false || strpos($tag_lower, 'multiple-selects') !== false) {
                                $mcq_flag =0;
                                $mappedType = 'MSQ';
                            }
                            self::update_stat($type_stats[$mappedType], $isAttempted, $isCorrect, $isSkipped);
                        }
                        // elseif ($mcq_flag == 1) {
                        //     $mappedType = 'MCQ';
                        //     self::update_stat($type_stats[$mappedType], $isAttempted, $isCorrect, $isSkipped);
                        // }
                    }

                    // Update total counts
                    self::update_stat($difficulty_stats['Total'], $isAttempted, $isCorrect, $isSkipped);
                    self::update_stat($subject_stats['Total'], $isAttempted, $isCorrect, $isSkipped);
                    self::update_stat($type_stats['Total'], $isAttempted, $isCorrect, $isSkipped);
                }
            }
        }
        $performance_data = array(
            'labels' => array('Exam 1', 'Exam 2', 'Exam 3', 'Exam 4'),
            'user_accuracy' => array(72, 84, 63, 91), // sample user accuracy %
            'topper_accuracy' => array(95, 96, 90, 98) // sample topper accuracy %
        );
        // echo "<pre>";
        // print_r($difficulty_stats);
        // echo "</pre>";
        // echo "<pre>";
        // print_r($subject_stats);
        // echo "</pre>";

        // Convert associative stats into arrays for Chart.js
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

    //helper to increment attempted/correct/skipped.
    private static function update_stat(&$stat, $isAttempted, $isCorrect, $isSkipped) {
        // echo "isAttempted: $isAttempted, isCorrect: $isCorrect, isSkipped: $isSkipped<br>";
        // $stat['total']++;
        if ($isAttempted) $stat['attempted']++;
        if ($isCorrect) $stat['correct']++;
        if ($isSkipped) $stat['skipped']++;
    }
}
