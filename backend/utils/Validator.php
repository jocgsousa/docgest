<?php

class Validator {
    private $data;
    private $errors = [];
    
    public function __construct($data = []) {
        $this->data = $data;
    }
    
    /**
     * Valida se o campo é obrigatório
     */
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
            $this->errors[$field] = $message ?: "O campo {$field} é obrigatório";
        }
        return $this;
    }
    
    /**
     * Valida email
     */
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?: "O campo {$field} deve ser um email válido";
        }
        return $this;
    }
    
    /**
     * Valida tamanho mínimo
     */
    public function min($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = $message ?: "O campo {$field} deve ter pelo menos {$length} caracteres";
        }
        return $this;
    }
    
    /**
     * Valida tamanho máximo
     */
    public function max($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = $message ?: "O campo {$field} deve ter no máximo {$length} caracteres";
        }
        return $this;
    }
    
    /**
     * Valida se é numérico
     */
    public function numeric($field, $message = null) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?: "O campo {$field} deve ser numérico";
        }
        return $this;
    }
    
    /**
     * Valida se é inteiro
     */
    public function integer($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->errors[$field] = $message ?: "O campo {$field} deve ser um número inteiro";
        }
        return $this;
    }
    
    /**
     * Valida se está em uma lista de valores
     */
    public function in($field, $values, $message = null) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $valuesList = implode(', ', $values);
            $this->errors[$field] = $message ?: "O campo {$field} deve ser um dos seguintes valores: {$valuesList}";
        }
        return $this;
    }
    
    /**
     * Valida formato de data
     */
    public function date($field, $format = 'Y-m-d', $message = null) {
        if (isset($this->data[$field])) {
            $date = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$date || $date->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?: "O campo {$field} deve ser uma data válida no formato {$format}";
            }
        }
        return $this;
    }
    
    /**
     * Valida se é uma URL válida
     */
    public function url($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
            $this->errors[$field] = $message ?: "O campo {$field} deve ser uma URL válida";
        }
        return $this;
    }
    
    /**
     * Valida usando regex
     */
    public function regex($field, $pattern, $message = null) {
        if (isset($this->data[$field]) && !preg_match($pattern, $this->data[$field])) {
            $this->errors[$field] = $message ?: "O campo {$field} não atende ao formato exigido";
        }
        return $this;
    }
    
    /**
     * Valida se o valor é único no banco de dados
     */
    public function unique($field, $table, $column = null, $excludeId = null, $message = null) {
        if (!isset($this->data[$field])) {
            return $this;
        }
        
        $column = $column ?: $field;
        
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = :value";
            $params = [':value' => $this->data[$field]];
            
            if ($excludeId) {
                $sql .= " AND id != :exclude_id";
                $params[':exclude_id'] = $excludeId;
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->fetchColumn() > 0) {
                $this->errors[$field] = $message ?: "O valor do campo {$field} já está em uso";
            }
            
        } catch (Exception $e) {
            error_log('Validation error: ' . $e->getMessage());
        }
        
        return $this;
    }
    
    /**
     * Valida se o valor existe no banco de dados
     */
    public function exists($field, $table, $column = null, $message = null) {
        if (!isset($this->data[$field])) {
            return $this;
        }
        
        $column = $column ?: $field;
        
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = :value";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':value' => $this->data[$field]]);
            
            if ($stmt->fetchColumn() == 0) {
                $this->errors[$field] = $message ?: "O valor do campo {$field} não existe";
            }
            
        } catch (Exception $e) {
            error_log('Validation error: ' . $e->getMessage());
        }
        
        return $this;
    }
    
    /**
     * Valida confirmação de senha
     */
    public function confirmed($field, $message = null) {
        $confirmField = $field . '_confirmation';
        
        if (isset($this->data[$field]) && isset($this->data[$confirmField])) {
            if ($this->data[$field] !== $this->data[$confirmField]) {
                $this->errors[$field] = $message ?: "A confirmação do campo {$field} não confere";
            }
        }
        
        return $this;
    }
    
    /**
     * Valida CPF
     */
    public function cpf($field, $message = null) {
        if (!isset($this->data[$field])) {
            return $this;
        }
        
        $cpf = preg_replace('/[^0-9]/', '', $this->data[$field]);
        
        if (strlen($cpf) != 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            $this->errors[$field] = $message ?: "O campo {$field} deve ser um CPF válido";
            return $this;
        }
        
        // Validação do dígito verificador
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                $this->errors[$field] = $message ?: "O campo {$field} deve ser um CPF válido";
                break;
            }
        }
        
        return $this;
    }
    
    /**
     * Valida CNPJ
     */
    public function cnpj($field, $message = null) {
        if (!isset($this->data[$field])) {
            return $this;
        }
        
        $cnpj = preg_replace('/[^0-9]/', '', $this->data[$field]);
        
        if (strlen($cnpj) != 14) {
            $this->errors[$field] = $message ?: "O campo {$field} deve ser um CNPJ válido";
            return $this;
        }
        
        // Validação do dígito verificador
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $weights1[$i];
        }
        
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;
        
        if ($cnpj[12] != $digit1) {
            $this->errors[$field] = $message ?: "O campo {$field} deve ser um CNPJ válido";
            return $this;
        }
        
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $weights2[$i];
        }
        
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;
        
        if ($cnpj[13] != $digit2) {
            $this->errors[$field] = $message ?: "O campo {$field} deve ser um CNPJ válido";
        }
        
        return $this;
    }
    
    /**
     * Verifica se há erros
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Retorna os erros
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Valida dados com regras específicas
     */
    public function validate($data, $rules) {
        $this->data = $data;
        $this->errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            
            foreach ($fieldRules as $rule) {
                if (strpos($rule, ':') !== false) {
                    list($ruleName, $ruleValue) = explode(':', $rule, 2);
                } else {
                    $ruleName = $rule;
                    $ruleValue = null;
                }
                
                switch ($ruleName) {
                    case 'required':
                        $this->required($field);
                        break;
                    case 'email':
                        $this->email($field);
                        break;
                    case 'min':
                        $this->min($field, (int)$ruleValue);
                        break;
                    case 'max':
                        $this->max($field, (int)$ruleValue);
                        break;
                    case 'cnpj':
                        $this->cnpj($field);
                        break;
                }
            }
        }
        
        return !$this->hasErrors();
    }
    
    /**
     * Valida e retorna erros se houver
     */
    public function validateAndRespond() {
        if ($this->hasErrors()) {
            Response::validation($this->errors);
        }
        
        return true;
    }
    
    /**
     * Método estático para validação rápida
     */
    public static function make($data) {
        return new self($data);
    }
}

?>