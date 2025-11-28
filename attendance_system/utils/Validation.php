<?php
class Validation {
    private $errors = [];

    public function validate($data, $rules) {
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $rulesList = explode('|', $fieldRules);

            foreach ($rulesList as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    private function applyRule($field, $value, $rule) {
        if (strpos($rule, ':') !== false) {
            list($ruleName, $ruleValue) = explode(':', $rule);
        } else {
            $ruleName = $rule;
            $ruleValue = null;
        }

        switch ($ruleName) {
            case 'required':
                if (empty($value)) {
                    $this->errors[$field] = ucfirst($field) . " is required";
                }
                break;
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = ucfirst($field) . " must be a valid email";
                }
                break;
            case 'min':
                if (!empty($value) && strlen($value) < (int)$ruleValue) {
                    $this->errors[$field] = ucfirst($field) . " must be at least " . $ruleValue . " characters";
                }
                break;
            case 'max':
                if (!empty($value) && strlen($value) > (int)$ruleValue) {
                    $this->errors[$field] = ucfirst($field) . " must not exceed " . $ruleValue . " characters";
                }
                break;
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->errors[$field] = ucfirst($field) . " must be numeric";
                }
                break;
        }
    }

    public function getErrors() {
        return $this->errors;
    }
}
?>