<?php declare(strict_types=1);

namespace Limepie;

/**
 * 🚀 계층적 트리 빌더 (개선된 버전).
 *
 * 성능 최적화 포인트:
 * 1. O(n) 시간복잡도로 children 매핑 생성
 * 2. 재귀 함수로 깔끔한 HTML 생성
 * 3. 메모리 효율적인 참조 사용
 * 4. 불필요한 배열 순회 최소화
 * 5. order_number와 depth의 유연한 위치 지원
 */
class TreeBuilder
{
    private array $children = [];

    private array $items = [];

    /**
     * 배열 데이터를 ul/li 구조로 변환.
     */
    public function buildTree(array $data, array $options = []) : string
    {
        // 기본 옵션 설정
        $options = \array_merge([
            'ul_class'            => 'tree-list',
            'li_class'            => 'tree-item',
            'htmx'                => true,
            'add_data_attributes' => true,
            'include_url'         => false,
            'custom_formatter'    => null,
            'current_key'         => 'current_seq',
            'parent_key'          => 'parent_seq',
            'sort_by_order'       => true,  // 🔥 order_number로 정렬 여부
        ], $options);

        // 1단계: 성능 최적화된 children 매핑 생성 (O(n))
        $this->buildChildrenMapping($data, $options);

        // 2단계: 루트 노드들 찾기
        $rootNodes = $this->children[0] ?? [];

        if (empty($rootNodes)) {
            return '<ul class="' . \htmlspecialchars($options['ul_class']) . '"><!-- 데이터 없음 --></ul>';
        }

        // 3단계: HTML 생성
        return $this->renderTree($rootNodes, $options, 0);
    }

    /**
     * 🔥 핵심 성능 최적화: 새로운 데이터 구조에 맞게 children 매핑 생성.
     */
    private function buildChildrenMapping(array $data, array $options) : void
    {
        $this->children = [];
        $this->items    = [];

        // O(n) 시간복잡도로 parent-children 관계 매핑
        foreach ($data as $item) {
            $currentSeq = $item[$options['current_key']];  // 🔥 current_seq 사용
            $parentSeq  = $item[$options['parent_key']];    // 🔥 parent_seq 사용

            // 아이템 저장 (current_seq를 키로 사용)
            $this->items[$currentSeq] = $item;

            // children 배열에 추가
            if (!isset($this->children[$parentSeq])) {
                $this->children[$parentSeq] = [];
            }
            $this->children[$parentSeq][] = $currentSeq;
        }

        // 🔥 order_number로 정렬
        if ($options['sort_by_order']) {
            foreach ($this->children as $parentSeq => $childrenSeqs) {
                \usort($this->children[$parentSeq], function ($a, $b) {
                    $orderA = $this->getValueFromItemOrParams($this->items[$a], 'order_number', 0);
                    $orderB = $this->getValueFromItemOrParams($this->items[$b], 'order_number', 0);

                    return $orderA <=> $orderB;
                });
            }
        }
    }

    /**
     * 🔧 item 또는 item['params']에서 값을 가져오는 헬퍼 함수.
     *
     * @param null|mixed $default
     */
    private function getValueFromItemOrParams(array $item, string $key, $default = null)
    {
        // 먼저 item에서 직접 찾기
        if (isset($item[$key])) {
            return $item[$key];
        }

        // item['params']에서 찾기
        if (isset($item['params'][$key])) {
            return $item['params'][$key];
        }

        return $default;
    }

    /**
     * 재귀적으로 HTML 트리 렌더링
     */
    private function renderTree(array $nodeIds, array $options, int $depth) : string
    {
        $ulClass = 0 === $depth ? $options['ul_class'] : '';

        if ($depth > 0) {
            $ulClass .= ' d-none';
        }

        if ($options['active_depth'] > 0 && $depth < ($options['active_depth'] - 1)) {
            $ulClass .= ' deactive-depth';
        }

        if ($options['active_depth'] > 0 && $depth === ($options['active_depth'] - 1)) {
            $ulClass .= ' last-depth';
        }

        $html = '<ul' . ($ulClass ? ' class="' . \htmlspecialchars($ulClass) . '"' : '') . ">\n";

        foreach ($nodeIds as $nodeId) {
            $item = $this->items[$nodeId];
            $html .= $this->renderNode($item, $options, $depth);
        }

        $html .= "</ul>\n";

        return $html;
    }

    /**
     * 개별 노드 렌더링
     */
    private function renderNode(array $item, array $options, int $depth) : string
    {
        $currentSeq  = $item[$options['current_key']];
        $name        = \htmlspecialchars($item['name']);
        $hasChildren = isset($this->children[$currentSeq]) && !empty($this->children[$currentSeq]);

        // 들여쓰기
        $indent = \str_repeat('  ', $depth + 1);

        // li 태그 속성 구성
        $liClass = $options['li_class'];

        $liClass .= ' depth-' . $depth;

        if ($item['active'] ?? false) {
            $liClass .= ' active';
        }

        $liAttributes = '';

        if ($liClass) {
            $liAttributes = ' class="' . $liClass . '"';
        }

        if ($options['add_data_attributes']) {
            $liAttributes .= ' data-seq="' . ($item['seq'] ?? '') . '"';  // 원본 seq
            $liAttributes .= ' data-current-seq="' . $currentSeq . '"';
            $liAttributes .= ' data-parent-seq="' . $item[$options['parent_key']] . '"';

            // depth와 order_number를 유연하게 처리
            $itemDepth   = $this->getValueFromItemOrParams($item, 'depth', 1);
            $orderNumber = $this->getValueFromItemOrParams($item, 'order_number', 0);

            $liAttributes .= ' data-depth="' . $itemDepth . '"';
            $liAttributes .= ' data-order="' . $orderNumber . '"';

            $uniqid = $this->getValueFromItemOrParams($item, 'uniqid', '');

            if ($uniqid) {
                $liAttributes .= ' data-uniqid="' . \htmlspecialchars($uniqid) . '"';
            }
        }

        $html = $indent . "<li{$liAttributes}>\n";

        // 커스텀 포매터가 있으면 사용
        if ($options['custom_formatter'] && \is_callable($options['custom_formatter'])) {
            $content = \call_user_func($options['custom_formatter'], $item, $hasChildren, $depth);
        } else {
            $content = $this->formatDefaultContent($item, $options, $hasChildren);
        }

        $html .= $indent . '  ' . $content . "\n";

        // 자식 노드 렌더링
        if ($hasChildren) {
            $childrenHtml = $this->renderTree($this->children[$currentSeq], $options, $depth + 1);
            // 들여쓰기 조정
            $childrenHtml = \preg_replace('/^/m', $indent . '  ', $childrenHtml);
            $html .= $childrenHtml;
        }

        $html .= $indent . "</li>\n";

        return $html;
    }

    /**
     * 기본 콘텐츠 포맷.
     */
    private function formatDefaultContent(array $item, array $options, bool $hasChildren) : string
    {
        $name  = \htmlspecialchars($item['name']);
        $uuid  = $this->getValueFromItemOrParams($item, 'uuid', '');
        $depth = $this->getValueFromItemOrParams($item, 'depth', 1);

        if ($options['htmx']) {
            return '<a href="?keyword=' . $uuid . '" hx-boost="true" class="text-decoration-none">' . $name . '</a>';
        }

        if ($hasChildren) {
            return '<span class="has-children depth-' . $depth . '">' . $name . '</span>';
        }

        return '<span class="depth-' . $depth . '">' . $name . '</span>';
    }

    /**
     * 🔍 성능 통계 정보.
     */
    public function getStats() : array
    {
        return [
            'total_items'   => \count($this->items),
            'total_parents' => \count($this->children),
            'root_items'    => \count($this->children[0] ?? []),
            'max_depth'     => $this->getMaxDepth(),
            'memory_usage'  => \memory_get_usage(true),
            'peak_memory'   => \memory_get_peak_usage(true),
        ];
    }

    /**
     * 최대 깊이 계산.
     */
    private function getMaxDepth() : int
    {
        $maxDepth = 0;

        foreach ($this->items as $item) {
            $depth = $this->getValueFromItemOrParams($item, 'depth', 1);

            if ($depth > $maxDepth) {
                $maxDepth = $depth;
            }
        }

        return $maxDepth;
    }
}

// /**
//  * 🎯 사용 예제들 - 다양한 데이터 구조 지원
//  */

// // === 예제 1: depth와 order_number가 item에 직접 있는 경우 ===
// $dataType1 = [
//     [
//         'current_seq' => 1,
//         'parent_seq' => 0,
//         'seq' => 601,
//         'name' => '먹다',
//         'depth' => 1,
//         'order_number' => 1,
//         'uniqid' => 'yf5wxx48vknzk'
//     ],
//     [
//         'current_seq' => 2,
//         'parent_seq' => 1,
//         'seq' => 430,
//         'name' => '음식',
//         'depth' => 2,
//         'order_number' => 1,
//         'uniqid' => 'esedcbguzjvtm'
//     ],
// ];

// // === 예제 2: depth와 order_number가 params에 있는 경우 ===
// $dataType2 = [
//     [
//         'current_seq' => 1,
//         'parent_seq' => 0,
//         'seq' => 601,
//         'name' => '먹다',
//         'params' => [
//             'depth' => 1,
//             'order_number' => 1,
//             'uniqid' => 'yf5wxx48vknzk'
//         ]
//     ],
//     [
//         'current_seq' => 2,
//         'parent_seq' => 1,
//         'seq' => 430,
//         'name' => '음식',
//         'params' => [
//             'depth' => 2,
//             'order_number' => 1,
//             'uniqid' => 'esedcbguzjvtm'
//         ]
//     ],
// ];

// // === 예제 3: 혼합된 경우 (일부는 직접, 일부는 params에) ===
// $dataType3 = [
//     [
//         'current_seq' => 1,
//         'parent_seq' => 0,
//         'seq' => 601,
//         'name' => '먹다',
//         'depth' => 1,  // 직접
//         'params' => [
//             'order_number' => 1,  // params에
//             'uniqid' => 'yf5wxx48vknzk'
//         ]
//     ],
//     [
//         'current_seq' => 2,
//         'parent_seq' => 1,
//         'seq' => 430,
//         'name' => '음식',
//         'params' => [
//             'depth' => 2,        // params에
//             'order_number' => 1, // params에
//             'uniqid' => 'esedcbguzjvtm'
//         ]
//     ],
// ];

// // 모든 타입의 데이터에 대해 동일하게 작동
// $builder = new TreeBuilder();

// echo "=== 타입 1 (직
