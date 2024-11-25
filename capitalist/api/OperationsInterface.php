<?php


namespace capitalist\api;


interface OperationsInterface
{

    const OPERATION_GET_TOKEN = 'get_token',
        OPERATION_GET_BATCH_INFO = 'get_batch_info',
        OPERATION_REGISTER_INVITEE = 'register_invitee',
        OPERATION_GET_HISTORY_DEPRECATED = 'get_documents_history',
        OPERATION_GET_HISTORY_EXT = 'get_documents_history_ext',
        OPERATION_GET_HISTORY_TEST = 'get_documents_history_test',
        OPERATION_GET_ACCOUNTS = 'get_accounts',
        OPERATION_CREATE_ACCOUNT = 'create_account',
        OPERATION_GET_DOCUMENT_FEE = 'get_document_fee',
        OPERATION_IMPORT_BATCH_ADV = 'import_batch_advanced',
        OPERATION_PROCESS_BATCH = 'process_batch',
        OPERATION_GET_CASHIN_REQUISITES = 'get_cashin_requisites',
        OPERATION_REGISTRATION_EMAIL_CONFIRM = 'registration_email_confirm',
        OPERATION_PASSWORD_RECOVERY = 'password_recovery',
        OPERATION_PASSWORD_RECOVERY_GENERATE_CODE = 'password_recovery_generate_code',
        OPERATION_GET_EMAIL_VERIFICATION_CODE = 'profile_get_verification_code',
        OPERATION_IS_VERIFIED_ACCOUNT = 'is_verified_account',
        OPERATION_ADD_NOTIFICATION = 'add_payment_notification',
        OPERATION_DOCUMENTS_SEARCH = 'documents_search',
        OPERATION_GCASH_DATA = 'gcash_data'
    ;

}