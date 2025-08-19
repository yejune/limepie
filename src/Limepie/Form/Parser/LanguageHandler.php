<?php

namespace Limepie\Form\Parser;

use Limepie\arr;
use Limepie\Di;
use Limepie\Exception;
use Limepie\Form\Parser;

/**
 * 언어 처리를 담당하는 클래스.
 *
 * 이 클래스는 폼 요소의 다국어 지원을 처리하는 역할을 합니다.
 * 단일 요소 또는 다중 항목에 대해 언어별 입력 필드를 생성하고 구성합니다.
 *
 * 주요 기능:
 * - 단일 요소의 다국어 변환 (예: name → name_langs)
 * - 다중 항목의 다국어 지원 (예: questions[] 배열 요소)
 * - 언어별 스타일 및 레이블 관리
 * - 기본값 및 언어 제한 처리
 *
 * 사용 예:
 * ```yaml
 * name:
 *   type: text
 *   lang: append  # 다국어 지원 활성화 (원본 필드 유지 + 언어별 필드 추가)
 *   langs:        # 지원할 언어 지정 (선택적)
 *     ko: true    # 한국어 지원
 *     en: true    # 영어 지원
 * ```
 */
class LanguageHandler
{
    /**
     * 다중 항목의 언어 설정 처리.
     *
     * 배열 형태의 요소(예: questions[])에 대한 언어 설정을 처리합니다.
     * 배열 요소 내에서 언어별 하위 필드를 생성합니다.
     *
     * @param string $key      요소 키 (예: 'questions[]')
     * @param array  $value    요소 설정 배열
     * @param string $basepath 기본 경로 (파일 참조용)
     *
     * @return array 처리된 다국어 설정 배열
     *
     * @throws \Exception 그룹 타입에 대한 다중 언어 지원 시도 또는 lang_key 누락 시
     */
    public function processMultiple(string $key, array $value, string $basepath) : array
    {
        // 다중 요소(배열)에서는 그룹 타입에 대한 언어 설정을 지원하지 않음
        if ('group' === $value['type']) {
            throw new \Exception('[] multiple은 lang옵션을 지원하지 않습니다. group 하위로 옮기세요.');
        }

        // 다중 요소에서는 언어 필드의 키를 지정해야 함
        if (!isset($value['lang_key'])) {
            throw new \Exception('multiple type에서 lang 지정시 하위에서 사용할 lang_key 가 필요합니다.');
        }

        // 언어 키를 사용하여 그룹 형태로 변환
        $langKey                                 = $value['lang_key'];
        $default                                 = $value;
        $orginType                               = $default['type'];
        $default['type']                         = 'group';             // 그룹으로 변경
        $default['properties'][$langKey]         = $default;            // 원래 설정을 하위로 이동
        $default['properties'][$langKey]['type'] = $orginType;          // 원래 타입 복원

        // append 모드이고 특정 언어가 지정되지 않은 경우 required 제약 제거
        if ('append' === $default['lang'] && !isset($default['langs'])) {
            unset($default['properties'][$langKey]['rules']['required']);
        }

        // 중복 및 불필요한 속성 제거
        unset(
            $default['lang'],
            $default['lang_key'],
            $default['rules'],
            $default['label'],
            $default['properties'][$langKey]['multiple'],
            $default['properties'][$langKey]['sortable'],
            $default['properties'][$langKey]['display_switch'],
            $default['properties'][$langKey]['display_target'],
            $default['properties'][$langKey]['display_target_condition'],
            $default['properties'][$langKey]['display_target_condition_class'],
            $default['properties'][$langKey]['display_target_condition_style']
        );

        // 하위 요소 및 전체 구조 재귀적 처리
        $default['properties'][$langKey] = Parser::process($default['properties'][$langKey], $basepath);

        return Parser::process($default, $basepath);
    }

    /**
     * 단일 항목의 언어 설정 처리.
     *
     * 일반 폼 요소(예: name, email 등)에 대한 언어 설정을 처리합니다.
     * 원본 필드와 '_langs' 접미사가 붙은 언어별 필드 그룹을 생성합니다.
     *
     * @param string $key      요소 키 (예: 'name')
     * @param array  $value    요소 설정 배열
     * @param string $basepath 기본 경로 (파일 참조용)
     *
     * @return array 처리된 설정 배열 ['original' => 원본필드, 'langs' => 언어필드그룹]
     */
    public function processSingle(string $key, array $value, string $basepath) : array
    {
        // 레이블 제거 및 append 모드 여부 확인
        $isRemoveLabel = ($value['remove_lang_title'] ?? false);
        $isRemoveFrame = ($value['remove_lang_frame'] ?? false);
        $isLangAppend  = 'append' === $value['lang'];
        $orgClass      = $value['class'] ?? '';
        $result        = [];

        // append 모드인 경우 원본 필드도 유지
        if ('append' === $value['lang']) {
            if ($isRemoveLabel) {
                $value['class'] = $orgClass . ' pb-1';  // 하단 패딩 추가
            }
            $result['original'] = Parser::process($value, $basepath);
        }

        // 원본 클래스 복원 및 언어별 속성 준비
        $value['class'] = $orgClass;
        $default        = $value;

        // 언어 필드에서 제외할 속성들 제거
        unset(
            $default['lang'],
            $default['class'],
            $default['style'],
            $default['description'],
            $default['default'],

            $default['display_switch'],
            $default['display_target'],
            $default['display_target_condition'],
            $default['display_target_condition_class'],
            $default['display_target_condition_style']
        );
        $appendLangProperties = $default;

        // append 모드이고 특정 언어가 지정되지 않은 경우 required 제약 제거
        if ('append' === $value['lang'] && !isset($value['langs'])) {
            unset($appendLangProperties['rules']['required']);
        }

        // 레이블 처리 - 배열인 경우 현재 언어에 맞는 레이블 선택
        if (isset($value['label'])) {
            $label = \is_array($value['label'])
                ? ($value['label'][\Limepie\get_language()] ?? '')
                : $value['label'];
        } else {
            $label = '';
        }

        // 언어별 속성 생성
        $langProperties = $this->buildLanguageProperties($appendLangProperties, $isRemoveLabel);

        // 특정 언어만 사용하도록 제한된 경우 필터링
        if (isset($value['langs'])) {
            $langProperties['properties'] = $this->filterLanguageProperties($langProperties, $value['langs']);
        }

        // 언어팩 이름 설정
        $languagePackName = \Limepie\__('core', '언어팩');

        if (isset($value['lang_name'])) {
            $languagePackName = $value['lang_name'];
        }

        // 언어 그룹 구성 생성
        $result['langs'] = $this->buildLanguageGroup(
            $label,
            $languagePackName,
            $langProperties['properties'] ?? [],
            $value,
            $isLangAppend,
            $isRemoveLabel,
            $isRemoveFrame,
            $basepath,
            $value['lang'] ?? ''
        );
        // \prx($langProperties, $result);

        return $result;
    }

    /**
     * 언어 속성 빌드.
     *
     * 각 지원 언어에 대한 속성 배열을 생성합니다.
     * 사용 가능한 언어 모델이 있으면 그것을 사용하고,
     * 없으면 기본 언어 세트(한국어, 영어, 일본어, 중국어)를 사용합니다.
     *
     * @param array $appendLangProperties 언어 요소에 추가할 공통 속성
     * @param bool  $isRemoveLabel        레이블 제거 여부
     *
     * @return array 언어 속성 및 정렬 순서 ['properties' => 속성, 'desiredOrder' => 순서]
     */
    private function buildLanguageProperties(array $appendLangProperties, bool $isRemoveLabel) : array
    {
        // 언어 모델 가져오기 (Di에서 언어 설정 조회)
        $languageModels = Di::getLanguageModels(null);
        $langProperties = [];

        // 언어 모델이 있고 두 개 이상인 경우 모델 사용
        if ($languageModels && $languageModels->toCount() > 1) {
            $desiredOrder = [];

            foreach ($languageModels as $languageModel) {
                // 언어 ID를 정렬 순서에 추가
                $desiredOrder[] = $languageModel->getId();

                // 언어별 기본 속성 설정 (국기 아이콘 등)
                $properties = ['prepend' => '<span class="lang-code" title="' . $languageModel->getName() . '">' . \strtoupper($languageModel->getId()) . '</span>'];

                // 레이블 제거 옵션 처리
                if ($isRemoveLabel) {
                    unset($properties['label'], $appendLangProperties['label']);
                    $properties['class'] = ($properties['class'] ?? '') . ' border-0 pb-1 pt-0 mt-1';
                } else {
                    $properties['label'] = \Limepie\__('core', $languageModel->getName());
                }

                // 공통 속성과 언어별 속성 병합
                $langProperties[$languageModel->getId()] = $properties + $appendLangProperties;
            }
        } else {
            // 언어 모델이 없거나 하나뿐인 경우 기본 언어 세트 사용
            $desiredOrder = ['ko', 'ja', 'en', 'zh'];

            $languages = [
                'ko' => ['name' => '한국어', 'locale' => 'kr'],
                'en' => ['name' => 'English', 'locale' => 'us'],
                'ja' => ['name' => '日本語', 'locale' => 'jp'],
                'zh' => ['name' => '中文', 'locale' => 'cn'],
            ];

            foreach ($languages as $code => $lang) {
                // 언어별 기본 속성 설정 (국기 아이콘 등)
                $properties = ['prepend' => '<span class="lang-code" title="' . $lang['name'] . '">' . \strtoupper($code) . '</span>'];

                // 레이블 제거 옵션 처리
                if ($isRemoveLabel) {
                    unset($properties['label'], $appendLangProperties['label']);
                    $properties['class'] = ($properties['class'] ?? '') . ' border-0 pb-1 pt-0 mt-1';
                } else {
                    $properties['label'] = \Limepie\__('core', $lang['name']);
                }

                // 공통 속성과 언어별 속성 병합
                $langProperties[$code] = $properties + $appendLangProperties;
            }
        }

        return ['properties' => $langProperties, 'desiredOrder' => $desiredOrder];
    }

    /**
     * 사용자 지정 언어 필터링.
     *
     * 'langs' 설정에 따라 지원할 언어만 필터링합니다.
     * 설정된 순서대로 언어를 정렬하고, 지원하지 않는 언어는 제외합니다.
     *
     * @param array $langPropertiesData 전체 언어 속성 데이터
     * @param mixed $langs              지원할 언어 설정 (배열 또는 연관 배열)
     *
     * @return array 필터링된 언어 속성 배열
     *
     * @throws Exception 지원하지 않는 언어 코드 지정 시
     */
    private function filterLanguageProperties(array $langPropertiesData, $langs) : array
    {
        $langProperties    = $langPropertiesData['properties'];
        $desiredOrder      = $langPropertiesData['desiredOrder'];
        $newLangProperties = [];

        // 연관 배열인 경우 (언어별 추가 설정이 있는 경우)
        if (arr::is_assoc($langs)) {
            // 원하는 순서대로 언어 키 정렬
            \uksort($langs, function ($a, $b) use ($desiredOrder) {
                $posA = \array_search($a, $desiredOrder);
                $posB = \array_search($b, $desiredOrder);

                return $posA - $posB;
            });

            // 각 언어별 처리
            foreach ($langs as $langKey => $langProperty) {
                if (isset($langProperties[$langKey])) {
                    if ($langProperty) {
                        // 언어별 추가 설정이 있는 경우 병합
                        unset($langProperties[$langKey]['langs']);
                        $newLangProperties[$langKey] = arr::merge_deep($langProperties[$langKey], $langProperty);
                    } else {
                        // 추가 설정 없이 기본 속성만 사용
                        $newLangProperties[$langKey] = $langProperties[$langKey];
                    }
                } else {
                    // 지원하지 않는 언어 코드
                    throw new Exception($langKey . ' 언어는 지원하지 않습니다.');
                }
            }
        } else {
            // 단순 배열인 경우 (언어 코드만 나열)
            foreach ($langs as $langKey) {
                if (isset($langProperties[$langKey])) {
                    $newLangProperties[$langKey] = $langProperties[$langKey];
                } else {
                    // 지원하지 않는 언어 코드
                    throw new Exception($langKey . ' 언어는 지원하지 않습니다.');
                }
            }
        }

        return $newLangProperties;
    }

    /**
     * 언어 그룹 구성 빌드.
     *
     * 언어별 필드를 포함하는 그룹 요소를 생성합니다.
     * 공통 스타일 및 레이블을 설정하고, 디스플레이 타겟 관련 속성을 유지합니다.
     *
     * @param string $label            요소 레이블
     * @param string $languagePackName 언어팩 이름
     * @param array  $value            원본 요소 설정
     * @param bool   $isLangAppend     append 모드 여부
     * @param bool   $isRemoveLabel    레이블 제거 여부
     * @param string $basepath         기본 경로
     *
     * @return array 처리된 언어 그룹 설정
     */
    private function buildLanguageGroup(
        string $label,
        string $languagePackName,
        array $langProperties,
        array $value,
        bool $isLangAppend,
        bool $isRemoveLabel,
        bool $isRemoveFrame,
        string $basepath,
        string $lang
    ) : array {
        // 클래스 설정 준비
        $langClass = '';

        if (isset($value['class'])) {
            $langClass .= ' ' . $value['class'];
        }

        $langGroupClass = '';

        if (isset($value['lang_group_class'])) {
            $langGroupClass .= ' ' . $value['lang_group_class'];
        }

        $appendGroupClass = '';

        if ($isRemoveLabel) {
            if ($isRemoveFrame) {
                $appendGroupClass = ' p-0 border-0';
            } else {
                $appendGroupClass = ' p-1';
            }
        }

        // 기본 그룹 구성 생성
        if ('append' == $lang) {
            $defaultClass = \trim($langClass) . ($isRemoveLabel ? ' border-0 pt-0 mt-1' : '');
        } else {
            $defaultClass = \trim($langClass);
        }
        $group = [
            'label'       => ($label ?? '') . ' - ' . $languagePackName,  // 레이블 - 언어팩
            'type'        => 'group',                                    // 그룹 타입
            'class'       => $defaultClass,  // 스타일 클래스
            'group_class' => \trim($langGroupClass) . $appendGroupClass,       // 그룹 스타일 클래스
            'properties'  => $langProperties,                            // 언어별 필드 속성
        ];

        // append 모드이고 레이블 제거 옵션이 있는 경우 레이블도 제거
        if ($isLangAppend && $isRemoveLabel) {
            unset($group['label']);
        }

        // 디스플레이 타겟 관련 속성 유지
        $displayKeys = [
            'display_target',
            'display_target_condition',
            'display_target_condition_class',
            'display_target_condition_style',
        ];

        foreach ($displayKeys as $displayKey) {
            if (isset($value[$displayKey])) {
                $group[$displayKey] = $value[$displayKey];
            }
        }

        // 최종 구성 처리 및 반환
        return Parser::process($group, $basepath);
    }
}
