<?php

declare(strict_types=1);

namespace Limepie\Form\Parser;

/**
 * 폼 요소의 조건부 표시를 관리하는 클래스.
 *
 * 이 클래스는 폼 구성에서 'display_switch' 설정을 처리하여
 * 특정 조건에 따라 폼 요소를 동적으로 표시하거나 숨기는 기능을 담당합니다.
 *
 * 주요 기능:
 * - 조건부 가시성 규칙 처리
 * - JavaScript 이벤트 핸들러 생성
 * - CSS 클래스 및 스타일 할당
 *
 * 예시 사용법:
 * ```yaml
 * is_company:
 *   type: choice
 *   items:
 *     0: 개인
 *     1: 기업
 *   display_switch:
 *     1:
 *       - company_name
 *       - business_number
 *     0:
 *       - individual_name
 * ```
 * 위 설정은 'is_company'의 값이 1일 때 'company_name'과 'business_number' 필드를 표시하고,
 * 0일 때는 'individual_name' 필드를 표시합니다.
 */
class DisplayScriptManager
{
    /**
     * display_switch 구성 처리.
     *
     * 폼 구성 배열을 순회하면서 'display_switch' 설정이 있는 요소를 찾아
     * 해당 요소의 가시성 설정을 처리합니다.
     *
     * @param array $arr 처리할 폼 구성 배열
     *
     * @return array 처리된 폼 구성 배열
     */
    public function process(array $arr) : array
    {
        foreach ($arr as $key => $fields) {
            // display_switch 설정이 없는 요소는 건너뜁니다
            if (!isset($fields['display_switch']) || !$fields['display_switch']) {
                continue;
            }

            // 각 요소의 가시성 설정을 위해 ElementVisibilityManager를 활용합니다
            $manager = new ElementVisibilityManager($arr, $fields, $key);
            $arr     = $manager->setupVisibility();
        }

        return $arr;
    }
}
