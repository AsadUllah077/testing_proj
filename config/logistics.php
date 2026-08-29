<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Banks available in logistics forms
    |--------------------------------------------------------------------------
    |
    | Keep stable keys because existing bank-charge records store the selected
    | key. The list covers the digital, public-sector, specialized, local
    | private, Islamic, and foreign commercial-bank categories maintained by
    | the State Bank of Pakistan.
    |
    */
    'banks' => [
        'hbl' => 'HBL - Habib Bank Limited',
        'ubl' => 'UBL - United Bank Limited',
        'mcb' => 'MCB Bank Limited',
        'abl' => 'ABL - Allied Bank Limited',
        'nbp' => 'NBP - National Bank of Pakistan',
        'mbl' => 'Meezan Bank Limited',
        'bank_alfalah' => 'Bank Alfalah Limited',
        'bank_al_habib' => 'Bank AL Habib Limited',
        'faysal' => 'Faysal Bank Limited',
        'askari' => 'Askari Bank Limited',
        'habib_metropolitan' => 'Habib Metropolitan Bank Limited',
        'sc' => 'Standard Chartered Bank Pakistan Limited',
        'soneri' => 'Soneri Bank Limited',
        'js_bank' => 'JS Bank Limited',
        'bank_of_punjab' => 'The Bank of Punjab',
        'bank_of_khyber' => 'The Bank of Khyber',
        'sindh_bank' => 'Sindh Bank Limited',
        'first_women_bank' => 'First Women Bank Limited',
        'bank_makramah' => 'Bank Makramah Limited',
        'samba' => 'Samba Bank Limited',
        'al_baraka' => 'Al Baraka Bank (Pakistan) Limited',
        'bankislami' => 'BankIslami Pakistan Limited',
        'dubai_islamic' => 'Dubai Islamic Bank Pakistan Limited',
        'mcb_islamic' => 'MCB Islamic Bank Limited',
        'ztbl' => 'Zarai Taraqiati Bank Limited',
        'ppcbl' => 'Punjab Provincial Cooperative Bank Limited',
        'bank_of_china' => 'Bank of China Limited - Pakistan Operations',
        'citibank' => 'Citibank N.A. - Pakistan',
        'deutsche_bank' => 'Deutsche Bank AG - Pakistan',
        'icbc' => 'Industrial and Commercial Bank of China - Pakistan',
        'easypaisa_bank' => 'Easypaisa Bank Limited',
        'mashreq_bank_pakistan' => 'Mashreq Bank Pakistan Limited',
        'raqami_islamic_digital' => 'Raqami Islamic Digital Bank Limited',
    ],

    'bank_contract_incoterms' => [
        'EXW' => 'EXW - Ex Works',
        'FCA' => 'FCA - Free Carrier',
        'FAS' => 'FAS - Free Alongside Ship',
        'FOB' => 'FOB - Free On Board',
        'CFR' => 'CFR - Cost and Freight',
        'CIF' => 'CIF - Cost, Insurance and Freight',
        'CPT' => 'CPT - Carriage Paid To',
        'CIP' => 'CIP - Carriage and Insurance Paid To',
        'DAP' => 'DAP - Delivered at Place',
        'DPU' => 'DPU - Delivered at Place Unloaded',
        'DAT' => 'DAT - Delivered at Terminal',
        'DDP' => 'DDP - Delivered Duty Paid',
    ],
];
