<?php
return [
    //'packagePath'=>base_path().'\\vendor\Amerhendy\Pdf\src\\',
    //define ('K_PATH_URL', '');
    'Routes'=>[
        'view'=>'PDF/View/',
        'download'=>'PDF/Download/'
    ],
    'fontsPath'=>'fonts/',
    'imagesPath'=>'images/',
    'pdfHeaderLogo'=>[
        'Src'=>'',
        'Width'=>'',
        'Height'=>'',
    ],
    'cachePath'=>'/tmp/',
    'BlankImage'=>'_blank.png',
    'PageSize'=>'A4',
    'PageOrientation'=> 'P', //L/P
    'PDFCreator'=> 'AmerHendy',
    'PDFAuthor'=> 'Amer Hendy',
    'PDFHeaderTitle'=> 'AmerPackage',
    'PDFHeaderString'=> "by Amerhendy",
    'PDFUnit'=> 'mm',
    'PdfMargin'=>[
        'MarginHeader'=> 5,
        'MarginFooter'=> 10,
        'MarginTop'=> 27,
        'MarginBottom'=> 25,
        'MarginLeft'=> 10,
        'MarginRight'=> 10,
    ],
    'Font'=>[
        'Main'=>['name'=> 'helvetica','Size'=>10],
        'Date'=>['name'=> 'courier','Size'=>8],
        'MONOSPACED'=>'courier',
    ],
    'ImageScaleRatio'=> 1.25,

    /**
     * Magnification factor for titles.
     */
    'HeadMagnification'=> 1.1,

    /**
     * Height of cell respect font height.
     */
    'CellHeightRation'=> 1.25,

    /**
     * Title magnification respect main font size.
     */

    'TitleMagnifation'=> 1.3,

    /**
     * Reduction factor for small font.
     */
    'SmallRetio'=> 2/3,

    /**
     * Set to true to enable the special procedure used to avoid the overlappind of symbols on Thai language.
     */
    'ThaiTopchars'=> true,

    /**
     * If true allows to call TCPDF methods using HTML syntax
     * IMPORTANT: For security reason, disable this feature if you are printing user HTML content.
     */
    'CallsInHTML'=> true,

    /**
     * If true and PHP version is greater than 5, then the Error() method throw new exception instead of terminating the execution.
     */
    'TrowExeptionError'=> false,
];