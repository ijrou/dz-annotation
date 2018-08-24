<?php


function getglobal($key, $group = null) {
    //global $_G;
    $_G = array(
        'config' => array(
            'aa'=> array(
                'a1' => 1,
                'a2' => 2
            ),
            'bb'=>array(
                'b1' => 3,
                'b2' => 4
            )
        )
    );

    $key = explode('/', $group === null ? $key : $group.'/'.$key);      // explode 把字符串打散为数组
    $v = &$_G;
    foreach ($key as $k) {
        if (!isset($v[$k])) {
            return null;
        }
        $v = &$v[$k];
    }
    return $v;
}

$a = '132';
$b = &$a;
$b = $a.'4';
echo $a;