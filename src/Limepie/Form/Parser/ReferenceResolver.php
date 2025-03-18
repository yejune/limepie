<?php

namespace Limepie\Form\Parser;

use Limepie\Exception;
use Limepie\Form\Parser;

/**
 * 레퍼런스 해결을 담당하는 클래스.
 *
 * 이 클래스는 폼 구성에서 '$ref' 키워드를 통해 외부 파일이나
 * 다른 구성을 참조하는 기능을 처리합니다.
 *
 * 주요 기능:
 * - 외부 YAML 파일 참조 해결
 * - 특정 경로 내의 속성에 대한 선택적 접근
 * - 다중 참조 병합
 *
 * 사용 예:
 * ```yaml
 * $ref: "common-elements.yml"  # 전체 파일 참조
 * $ref: "(common-elements.yml).form.inputs"  # 특정 경로 참조
 * $ref: [                      # 다중 참조
 *   "inputs.yml",
 *   "validators.yml"
 * ]
 * ```
 */
class ReferenceResolver
{
    /**
     * 상대 경로 해결을 위한 기본 경로.
     *
     * @var string
     */
    private $basepath;

    /**
     * 생성자.
     *
     * 레퍼런스 해결에 사용할 기본 경로를 설정합니다.
     *
     * @param string $basepath 상대 경로 해결을 위한 기본 경로
     */
    public function __construct(string $basepath)
    {
        $this->basepath = $basepath;
    }

    /**
     * 레퍼런스 해결.
     *
     * 단일 참조 또는 참조 배열을 처리하고 해결된 데이터를 병합합니다.
     * 모든 참조를 해결한 후 전체 결과를 다시 refparse 함수로 처리합니다.
     *
     * @param mixed $value 참조 경로 또는 참조 경로 배열
     *
     * @return array 해결된 참조 데이터 병합 결과
     */
    public function resolve($value) : array
    {
        // 단일 문자열 참조를 배열로 변환
        if (!\is_array($value)) {
            $value = [$value];
        }

        $data = [];

        // 각 참조 경로에 대해 처리
        foreach ($value as $path) {
            $resolvedData = $this->resolveSingleReference($path);

            if ($resolvedData) {
                // 해결된 데이터를 결과 배열에 병합
                $data = \array_merge($data, $resolvedData);
            }
        }

        // 최종 병합된 데이터를 다시 refparse로 처리
        // (중첩된 참조 해결을 위함)
        return Parser::process($data, $this->basepath);
    }

    /**
     * 단일 레퍼런스 해결.
     *
     * 개별 참조 경로를 파싱하고 해당 파일을 로드하여
     * 지정된 경로 내의 데이터를 추출합니다.
     *
     * 두 가지 형식의 참조를 지원합니다:
     * 1. 일반 경로: "path/to/file.yml" - 전체 파일 또는 기본 properties 참조
     * 2. 경로 지정 참조: "(path/to/file.yml).section.subsection" - 특정 경로 참조
     *
     * @param string $path 참조 경로
     *
     * @return null|array 해결된 참조 데이터 또는 오류 시 null
     *
     * @throws Exception 참조 형식 오류 또는 경로를 찾을 수 없는 경우
     */
    private function resolveSingleReference(string $path)
    {
        $orgPath    = $path;  // 원본 경로 보존 (오류 메시지용)
        $detectKeys = ['properties'];  // 기본적으로 'properties' 키를 찾음

        // 괄호로 시작하는 경우 특정 경로 참조로 간주
        if (0 === \strpos($path, '(')) {
            if (\preg_match('#\((?P<path>.*)?\)\.(?P<keys>.*)#', $path, $m)) {
                // 파일 경로와 접근 키 경로 분리
                $path       = $m['path'];  // 괄호 안의 실제 파일 경로
                $detectKeys = \array_merge(\explode('.', $m['keys']), $detectKeys);  // 키 경로를 배열로 변환하고 'properties' 추가
            } else {
                // 형식이 잘못된 경우 예외 발생
                throw new Exception($orgPath . ' ref error');
            }
        }

        if ($path) {
            // 상대 경로인 경우 기본 경로 접두사 추가
            if (0 !== \strpos($path, '/') && $this->basepath) {
                $path = $this->basepath . '/' . $path;
            }

            // YAML 파일 파싱
            $yml           = \Limepie\yml_parse_file($path);
            $referenceData = $yml;

            // detectKeys 배열에 따라 중첩 데이터 접근
            foreach ($detectKeys as $detectKey) {
                if (isset($referenceData[$detectKey])) {
                    $referenceData = $referenceData[$detectKey];
                } else {
                    // 지정된 키가 없는 경우 예외 발생
                    throw new Exception($detectKey . ' not found2');
                }
            }

            return $referenceData;
        }

        // 경로가 비어있는 경우 예외 발생
        throw new Exception($orgPath . ' ref error');
    }
}
