<?php

if (!defined('QA_VERSION')) {
    header('Location: ../../');
    exit;
}

class qa_exam_stats_ajax_page
{
    public function suggest_requests()
    {
        return [];
    }

    public function match_request($request)
    {
        return $request === 'exam-stats-ajax';
    }

    public function process_request($request)
    {
        header('Content-Type: application/json; charset=utf-8');

        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
            $this->send_response(['success' => false, 'error' => 'Invalid request method.']);
        }

        $action = qa_post_text('action');
        $userid = (int) qa_post_text('userid');

        if ($userid <= 0) {
            $this->send_response(['success' => false, 'error' => 'Invalid user.']);
        }

        switch ($action) {
            case 'get_exam_stats_data':
                $this->handle_get_exam_stats_data($userid);
                break;

            case 'toggle_exam_stats_privacy':
                $this->handle_toggle_exam_stats_privacy($userid);
                break;

            case 'toggle_heatmap_privacy':
                $this->handle_toggle_heatmap_privacy($userid);
                break;

            case 'toggle_pointschart_privacy':
                $this->handle_toggle_pointschart_privacy($userid);
                break;

            default:
                $this->send_response(['success' => false, 'error' => 'Invalid action.']);
        }
    }

    private function handle_get_exam_stats_data($userid)
    {
        if (!$this->can_view_exam_stats($userid)) {
            $this->send_response(['success' => false]);
        }

        $exam_count = qa_db_read_one_value(
            qa_db_query_sub(
                'SELECT COUNT(*) FROM ^exam_results WHERE userid = #',
                $userid
            ),
            true
        );

        if ($exam_count <= 0) {
            $this->send_response(['success' => false]);
        }

        require_once QA_PLUGIN_DIR . 'exam-stats-graph/qa-exam-stats-graph.php';
        $data = qa_exam_stats_graph::get_stats_data_cached($userid, $exam_count);

        $this->send_response(['success' => true, 'stats' => $data]);
    }

    private function handle_toggle_exam_stats_privacy($userid)
    {
        if (!$this->is_owner($userid)) {
            $this->send_response(['success' => false]);
        }

        $current = (int) qa_db_usermeta_get($userid, 'exam_stats_public');
        $new_public = $current ? 0 : 1;
        qa_db_usermeta_set($userid, 'exam_stats_public', $new_public);

        $this->send_response(['success' => true, 'is_private' => !$new_public]);
    }

    private function handle_toggle_heatmap_privacy($userid)
    {
        if (!$this->is_owner($userid)) {
            $this->send_response(['success' => false]);
        }

        $current = (int) qa_db_usermeta_get($userid, 'heatmap_private');
        qa_db_usermeta_set($userid, 'heatmap_private', $current ? 0 : 1);

        $this->send_response(['success' => true, 'is_private' => !$current]);
    }

    private function handle_toggle_pointschart_privacy($userid)
    {
        if (!$this->is_owner($userid)) {
            $this->send_response(['success' => false]);
        }

        $current = (int) qa_db_usermeta_get($userid, 'pointschart_private');
        $new_private = $current ? 0 : 1;
        qa_db_usermeta_set($userid, 'pointschart_private', $new_private);

        $this->send_response(['success' => true, 'is_private' => (bool) $new_private]);
    }

    private function can_view_exam_stats($userid)
    {
        if ($this->is_owner($userid) || $this->is_admin()) {
            return true;
        }

        return (bool) ((int) qa_db_usermeta_get($userid, 'exam_stats_public'));
    }

    private function is_owner($userid)
    {
        $logged_in_userid = qa_get_logged_in_userid();

        return !empty($logged_in_userid) && ((int) $logged_in_userid === (int) $userid);
    }

    private function is_admin()
    {
        return qa_get_logged_in_level() >= QA_USER_LEVEL_SUPER;
    }

    private function send_response(array $payload)
    {
        echo json_encode($payload);
        qa_exit();
    }
}