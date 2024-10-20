<?php

declare(strict_types=1);

namespace Limepie;

// 초간단 인코딩 클래스
// 입력된 양의 정수를 4자리로 패딩하고 뒤집은 뒤 각 자리 숫자를 특정 문자로 매핑하여 고유하고 복호화 가능하며 변조 시 디코딩이 실패하는 코드를 생성하는 함수

class SimpleCode
{
    private static $validChars = ['D','E','F','G','H','J','K','M','N','P'];

    public static function encode($number)
    {
        if (!is_numeric($number) || $number < 0) {
            return null;
        }

        // 숫자가 4자리 미만인 경우 패딩
        $stringNumber = (string)$number;
        if (strlen($stringNumber) < 4) {
            $padded = str_pad($stringNumber, 4, '0', STR_PAD_LEFT);
        } else {
            $padded = $stringNumber;
        }

        // 숫자 뒤집기
        $reversed = strrev($padded);

        // 매핑된 문자 생성
        $letters = '';
        foreach (str_split($reversed) as $digit) {
            $letters .= self::$validChars[intval($digit)];
        }

        return $letters . $reversed;
    }

    public static function decode($code)
    {
        $length = strlen($code);
        if ($length % 2 !== 0 || $length < 8) {
            return null;
        } // 길이가 짝수이고 최소 8자리여야 함

        $halfLength = $length / 2;
        $letters = substr($code, 0, $halfLength);
        $numbers = substr($code, $halfLength);

        if (!ctype_digit($numbers)) {
            return null;
        }

        // 각 숫자와 문자 매핑 확인
        foreach (str_split($numbers) as $i => $digit) {
            $mappedChar = self::$validChars[intval($digit)] ?? null;
            if ($mappedChar !== $letters[$i]) {
                return null;
            }
        }

        // 원래 숫자 복원
        $originalNumber = strrev($numbers);
        return intval(ltrim($originalNumber, '0'));
    }
}


/*

// 1부터 1000까지 테스트
for ($number = 1; $number <= 2000; $number++) {
    // 인코딩
    $code = SimpleCode::encode($number);

    // 디코딩
    $decodedNumber = SimpleCode::decode($code);

    // 코드에서 한 자리 수정하여 디코딩 시도
    $modifiedCode = $code;

    // 임의의 위치 선택 (0부터 문자열 길이-1까지)
    $randomPosition = rand(0, strlen($code) - 1);
    $originalChar = $modifiedCode[$randomPosition];

    // 다른 문자로 변경
    if (ctype_digit($originalChar)) {
        // 숫자인 경우 다른 숫자로 변경
        $modifiedChar = ($originalChar === '0') ? '1' : '0';
    } else {
        // 문자일 경우 validChars에 없는 문자로 변경
        $modifiedChar = 'X';
    }
    $modifiedCode[$randomPosition] = $modifiedChar;

    // 수정된 코드 디코딩 시도
    $decodedModified = SimpleCode::decode($modifiedCode);

    // 수정된 코드의 디코딩 결과 문자열
    $modifiedDecodeResult = ($decodedModified === null) ? '디코딩 실패' : $decodedModified;

    // 결과 출력 (한 줄로)
    echo "입력값: {$number}, 인코딩: {$code}, 디코딩: {$decodedNumber}, 수정된 인코딩: {$modifiedCode}, 수정된 디코딩: {$modifiedDecodeResult}\n";
}

*/
