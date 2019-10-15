<?php

return [
    'status' => [
        'not_paid' => 'Transaction is failed or cancelled by the user.',
        'unexpected' => 'An unexpected payment error exception is occurred.',

        'incomplete_data' => 'Submitted information is incomplete.',
        'wrong_ip_or_merchant_id' => 'Merchant ID or Acceptor IP is not correct.',
        'shaparak_limited' => 'Amount should be above 100 Toman.',
        'insufficient_user_level' => 'Approved level of acceptor is lower than silver.',
        'request_not_found' => 'Request not found.',
        'unable_to_edit_request' => 'Unable to edit this request.',
        'no_financial_operation' => 'No financial operation for this transaction is found.',
        'failed_transaction' => 'Transaction is unsuccessful.',
        'amounts_not_equal' => 'Transaction amount does not match the amount paid.',
        'transaction_splitting_limited' => 'Transaction splitting number or amount limit is reached.',
        'method_access_denied' => 'Unable to access this payment method.',
        'invalid_additional_data' => 'Submitted information in AdditionalData is invalid.',
        'invalid_expiration_range' => 'Valid duration range for transaction id must be between 30 minutes to 45 days.',
        'request_archived' => 'Request archived.',
        'operation_succeed' => 'Operation was successful.',
        'already_verified' => 'Operation was successful but it has already been verified before.',
    ],
];
