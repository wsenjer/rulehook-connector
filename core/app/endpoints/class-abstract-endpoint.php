<?php

namespace RuleHook\Core\App\Endpoints;

if ( ! defined( 'ABSPATH' ) ) exit;


abstract class Abstract_Endpoint
{
    public function __construct()
    {
        add_action('wp_ajax_'.$this->action(), [$this, 'process']);
    }

    public function process()
    {
        check_ajax_referer($this->action(), '_ajax_nonce');

        $raw_input = file_get_contents('php://input');

        $data = json_decode($raw_input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            wp_send_json_error(['message' => 'Invalid JSON input.'], 400);
            exit;
        }

        // Get only the required fields for this endpoint
        $required_fields = $this->get_required_fields();

        if (!empty($required_fields)) {
            $filtered_data = [];
            foreach ($required_fields as $field) {
                if (isset($data[$field])) {
                    $filtered_data[$field] = $data[$field];
                }
            }
            $data = $filtered_data;
        }

        // Sanitize the filtered data
        $data = array_map(function ($value) {
            if (is_array($value)) {
                return array_map('sanitize_text_field', $value);
            }
            return sanitize_text_field($value);
        }, $data);

        $response = $this->callback($data);

        echo json_encode($response);
        exit;
    }


    /**
     * @param  array|string  $errors
     */
    protected function abort($error)
    {
        echo json_encode(['error' => $error]);
        exit;
    }

    /**
     * @param  array  $response
     */
    protected function output($response)
    {
        echo json_encode($response);
        exit;
    }

    protected function ok()
    {
        $this->output(['ok' => true]);
    }

    /**
     * @param mixed
     * @return array
     */
    abstract public function callback($data);

    /**
     * @return string
     */
    abstract public function action();

    protected function get_required_fields()
    {
        return [];
    }

}
