<?php
declare(strict_types=1);

namespace CL\jadro;

/**
 * Trieda pre zachytávanie a logovanie PHP chýb
 */
class ErrorHandler {
    private $oldErrorHandler;
    
    public function __construct() {
        // Registrácia vlastného error handlera
        $this->oldErrorHandler = set_error_handler([$this, 'handleError']);
        
        // Registrácia exception handlera
        set_exception_handler([$this, 'handleException']);
        
        // Registrácia handlera pre zachytávanie fatálnych chýb
        register_shutdown_function([$this, 'handleFatalError']);
        
        error_log('CL ErrorHandler: Inicializovaný');
    }
    
    /**
     * Spracovanie PHP chyby
     */
    public function handleError($errno, $errstr, $errfile, $errline) {
        // Ak je error reporting vypnutý, nerobíme nič
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        // Log chyby
        $this->logError($errno, $errstr, $errfile, $errline);
        
        // Ak má pôvodný handler spracovať chybu, delegujeme ju
        if ($this->oldErrorHandler) {
            return call_user_func($this->oldErrorHandler, $errno, $errstr, $errfile, $errline);
        }
        
        // Štandardné spracovanie chýb
        return false;
    }
    
    /**
     * Spracovanie výnimky
     */
    public function handleException($exception) {
        $this->logException($exception);
        
        // V AJAX požiadavke vrátime chybovú odpoveď
        if (wp_doing_ajax()) {
            wp_send_json_error([
                'message' => $exception->getMessage(),
                'file' => basename($exception->getFile()),
                'line' => $exception->getLine()
            ]);
            exit;
        }
    }
    
    /**
     * Spracovanie fatálnej chyby
     */
    public function handleFatalError() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
            $this->logError($error['type'], $error['message'], $error['file'], $error['line']);
            
            // V AJAX požiadavke vrátime chybovú odpoveď
            if (wp_doing_ajax()) {
                wp_send_json_error([
                    'message' => 'Fatálna chyba: ' . $error['message'],
                    'file' => basename($error['file']),
                    'line' => $error['line']
                ]);
                exit;
            }
        }
    }
    
    /**
     * Logovanie chyby
     */
    private function logError($errno, $errstr, $errfile, $errline) {
        $errorType = $this->getErrorType($errno);
        $message = "CL PHP $errorType: $errstr v $errfile na riadku $errline";
        
        // Log do WordPress
        error_log($message);
        
        // Log do vlastného logu
        $spravca = new SpravcaSuborov();
        $spravca->zapisDoLogu('PHP_ERROR', [
            'type' => $errorType,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'timestamp' => current_time('mysql')
        ]);
    }
    
    /**
     * Logovanie výnimky
     */
    private function logException($exception) {
        $message = "CL Exception: {$exception->getMessage()} v {$exception->getFile()} na riadku {$exception->getLine()}";
        
        // Log do WordPress
        error_log($message);
        error_log("Stack trace: " . $exception->getTraceAsString());
        
        // Log do vlastného logu
        $spravca = new SpravcaSuborov();
        $spravca->zapisDoLogu('EXCEPTION', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => current_time('mysql')
        ]);
    }
    
    /**
     * Prevod číselného kódu chyby na text
     */
    private function getErrorType($errno) {
        switch ($errno) {
            case E_ERROR:             return 'E_ERROR';
            case E_WARNING:           return 'E_WARNING';
            case E_PARSE:             return 'E_PARSE';
            case E_NOTICE:            return 'E_NOTICE';
            case E_CORE_ERROR:        return 'E_CORE_ERROR';
            case E_CORE_WARNING:      return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:     return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:   return 'E_COMPILE_WARNING';
            case E_USER_ERROR:        return 'E_USER_ERROR';
            case E_USER_WARNING:      return 'E_USER_WARNING';
            case E_USER_NOTICE:       return 'E_USER_NOTICE';
            case E_STRICT:            return 'E_STRICT';
            case E_RECOVERABLE_ERROR: return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:        return 'E_DEPRECATED';
            case E_USER_DEPRECATED:   return 'E_USER_DEPRECATED';
            default:                  return 'UNKNOWN_ERROR';
        }
    }
}
