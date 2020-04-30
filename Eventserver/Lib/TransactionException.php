<?php

namespace Lib;

/**
 * Transaction exceptions.
 */
class TransactionException extends \Exception {
    /**
     * Exception code as below:<br/>
     * <b>Error code start with "61" are transaction exceptions.</b><br/>
     * <pre>
     * <code>
     * 61001 => Bad message class key.
     * 61002 => Sender ('.$senderKey.') does not exists.
     * 61003 => Sender is not allowed to send specified class of message.
     * 61004 => Subscriber is not allowed to create message class.
     * 61005 => User is not allowed to enable send message by itself.
     * 61100 => Illformed broadcast message data.
     * 61101 => Illformed broadcast failure log message data.
     * 61199 => Sub transaction errors.
     * </code>
     * </pre>
     */
    public function __construct($message = null, $code = null, $subErrors = array(), $previous = null) {
        $message .= "\nSubErrors:\n";
        foreach ( $subErrors as $k => $v ) {
            $message .= '  #' . $v ['code'] . ' ' . $v ['message'] . "\n";
        }
        parent::__construct ( $message, $code, $previous );
    }
}