<?php

if (!defined('QA_VERSION'))  exit;

qa_register_plugin_module(
    'widget', 
    'qa-exam-stats-graph.php', 
    'qa_exam_stats_graph',
    'Exam Question Statistics Graph'
);

qa_register_plugin_module(
    'widget',
    'qa-activity-heatmap-widget.php',
    'qa_activity_heatmap_widget',
    'Activity Heatmap Widget'
);

qa_register_plugin_layer(
    'qa-exam-stats-graph-layer.php', 
    'Exam Question Statistics Graph Layer'
);