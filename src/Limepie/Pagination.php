<?php

declare(strict_types=1);

namespace Limepie;

class Pagination
{
    private $totalCount = 0;

    private $totalPages = 0;

    private $currentPage = 1;

    private $nextPage;

    private $prevPage;

    private $recordsPerPage = 10;

    private $pagesPerBlock = 9;

    private $viewStartEnd = true;

    private $urlPattern;

    public function __construct(
        $totalCount,
        $currentPage,
        $recordsPerPage = 10,
        $pagesPerBlock = 9,
        $urlPattern = '',
        $viewStartEnd = true
    ) {
        $this->totalCount     = (int) $totalCount;
        $this->recordsPerPage = (int) $recordsPerPage;
        $this->currentPage    = (int) ($currentPage ?: 1);
        $this->urlPattern     = $urlPattern;
        $this->pagesPerBlock  = (int) $pagesPerBlock;
        $this->viewStartEnd   = $viewStartEnd;

        $this->totalPages = (0 === $this->recordsPerPage ? 0 : (int) \ceil($this->totalCount / $this->recordsPerPage));
        $this->nextPage   = $this->currentPage < $this->totalPages ? $this->currentPage + 1 : null;
        $this->prevPage   = 1                  < $this->currentPage ? $this->currentPage - 1 : null;
    }

    public static function getHtml(
        $totalCount,
        $currentPage,
        $recordsPerPage = 10,
        $pagesPerBlock = 9,
        $urlPattern = '',
        $viewStartEnd = true
    ) {
        $pagination = new Pagination($totalCount, $currentPage, $recordsPerPage, $pagesPerBlock, $urlPattern, $viewStartEnd);

        return $pagination->toHtml();
    }

    public static function get(
        $totalCount,
        $currentPage,
        $recordsPerPage = 10,
        $pagesPerBlock = 9,
        $urlPattern = '',
        $viewStartEnd = true
    ) {
        $pagination = new Pagination($totalCount, $currentPage, $recordsPerPage, $pagesPerBlock, $urlPattern, $viewStartEnd);

        return $pagination->toArray();
    }

    public function getPages()
    {
        $pages = [];

        if (0 === $this->pagesPerBlock % 2) {
            ++$this->pagesPerBlock;
        }

        if ($this->totalPages <= $this->pagesPerBlock) {
            for ($i = 1; $i <= $this->totalPages; ++$i) {
                $pages[] = $this->createPage($i, $i === $this->currentPage);
            }
        } else {
            $pagesPerBlock = $this->pagesPerBlock;
            $numAdjacents  = (int) \floor(($pagesPerBlock - 1) / 2);

            if ($this->currentPage + $numAdjacents > $this->totalPages) {
                $startPage = $this->totalPages - $pagesPerBlock + 1; // + 2;
            } else {
                $startPage = $this->currentPage - $numAdjacents;
            }

            if (1 > $startPage) {
                $startPage = 1;
            }
            $endPage = $startPage + $pagesPerBlock - 1;

            if ($endPage >= $this->totalPages) {
                $endPage = $this->totalPages;
            }

            for ($i = $startPage; $i <= $endPage; ++$i) {
                $pages[] = $this->createPage($i, $i === $this->currentPage);
            }

            if ($this->viewStartEnd) {
                if (4 < $this->currentPage) {
                    $pages[0] = $this->createPage(1, 1 === $this->currentPage);
                    $pages[1] = $this->createEllipsisPage();
                }

                if ($this->totalPages - 4 >= $this->currentPage) {
                    \array_pop($pages);
                    \array_pop($pages);
                    $pages[] = $this->createEllipsisPage();
                    $pages[] = $this->createPage($this->totalPages, $this->currentPage === $this->totalPages);
                }
            }
        }

        return $pages;
    }

    public function toArray()
    {
        return [
            'totalPages'  => $this->totalPages,
            'currentPage' => $this->currentPage,
            'prevUrl'     => $this->getPageUrl($this->prevPage),
            'pages'       => $this->getPages(),
            'nextUrl'     => $this->getPageUrl($this->nextPage),
        ];
    }

    public function toHtml()
    {
        $pagination = $this->toArray();

        $html = '<ul class="pagination">';

        if ($pagination['prevUrl']) {
            $html .= '<li class="page-item prev"><a class="page-link" href="' . $pagination['prevUrl'] . '"><span class="page-text">&laquo;</span></a></li>'; // Previous
        } else {
            $html .= '<li class="page-item prev disabled"><a class="page-link"><span class="page-text">&laquo;</span></a></li>';
        }

        foreach ($pagination['pages'] as $page) {
            if ($page['url']) {
                $html .= '<li  class="page-item' . ($page['isCurrent'] ? ' active' : '') . '"><a class="page-link" href="' . $page['url'] . '"><span class="page-text">' . $page['num'] . '</span></a></li>';
            } else {
                $html .= '<li class="page-item ' . ('...' == $page['num'] ? 'ellipsis ' : '') . 'disabled"><a class="page-link"><span class="page-text">' . $page['num'] . '</span></a></li>';
            }
        }

        if ($pagination['nextUrl']) {
            $html .= '<li class="page-item next"><a class="page-link" href="' . $pagination['nextUrl'] . '"><span class="page-text">&raquo;</span></a></li>'; // Next
        } else {
            $html .= '<li class="page-item next disabled"><a class="page-link"><span class="page-text">&raquo;</span></a></li>';
        }
        $html .= '</ul>';

        return $html;
    }

    private function getPageUrl($pageNum = null)
    {
        if (!$pageNum) {
            return null;
        }

        return \str_replace('{=page}', (string) $pageNum, $this->urlPattern);
    }

    private function createPage($pageNum, $isCurrent = false)
    {
        return [
            'num'       => $pageNum,
            'url'       => $this->getPageUrl($pageNum),
            'isCurrent' => $isCurrent,
        ];
    }

    private function createEllipsisPage()
    {
        return [
            'num'       => '...',
            'url'       => null,
            'isCurrent' => false,
        ];
    }

    public static function getList(
        $countModel,
        $listModel = null,
        int $recordsPerPage = 10,
        int $pagesPerBlock = 9,
        $hash = null,
        $currentPage = null,
        $urlPattern = null,
        $returnTypeNull = null,
        ?int $maxPage = null, // 최대 페이지 제한 추가
        $page = null
    ) : array {
        if (!$listModel) {
            $listModel = clone $countModel;
        }

        $argPage = (int) ($page ?? $_REQUEST['page'] ?? 1);

        $totalCount = $countModel->getCount();

        if (!$urlPattern) {
            $qs = $_SERVER['QUERY_STRING'] ?? '';

            if (0 < \strlen($qs)) {
                $qs = '?' . $qs;
            }

            if (1 === \preg_match('#(\?|&)page=(\d+)#', $qs, $m)) {
                $query = \preg_replace('#(\?|&)page=(\d+)#', '$1page={=page}', $qs);
            } else {
                $query = $qs . (0 < \strlen($qs) ? '&' : '?') . 'page={=page}';
            }
            $urlPattern = $query;

            if ($hash) {
                $urlPattern .= $hash;
            }
        }

        if ($currentPage) {
            $currentPage = (int) $currentPage;
        } else {
            $currentPage = (int) $argPage;
        }

        if ($currentPage < 1) {
            $currentPage = 1;
        }
        $totalPages = (0 === $recordsPerPage ? 0 : (int) \ceil($totalCount / $recordsPerPage));

        // 최대 페이지 제한 적용
        if (null !== $maxPage && $totalPages > $maxPage) {
            $totalPages = $maxPage;
        }

        if ($totalPages && $currentPage > $totalPages) {
            if ($returnTypeNull) {
                return [null, null, $totalCount, $totalPages];
            }

            $fixUrl = \str_replace('{=page}', (string) $totalPages, $urlPattern);

            if (!\headers_sent()) {
                \header('Location: ' . $fixUrl);
            } else {
                echo '<script type="text/javascript">';
                echo 'window.location.href="' . $fixUrl . '";';
                echo '</script>';
            }

            exit;
        }

        $offset     = ($currentPage - 1) * $recordsPerPage;
        $pagination = Pagination::getHtml($totalCount, $currentPage, $recordsPerPage, $pagesPerBlock, $urlPattern);
        $listModels = $listModel->limit($offset, $recordsPerPage)->gets();

        return [$listModels, $pagination, $totalCount, $totalPages];
    }

    public static function getNav($totalCount, int $recordsPerPage = 10, int $pagesPerBlock = 9) : array
    {
        $qs = $_SERVER['QUERY_STRING'] ?? '';

        if (0 < \strlen($qs)) {
            $qs = '?' . $qs;
        }

        if (1 === \preg_match('#(\?|&)page=(\d+)#', $qs, $m)) {
            $query = \preg_replace('#(\?|&)page=(\d+)#', '$1page={=page}', $qs);
        } else {
            $query = $qs . (0 < \strlen($qs) ? '&' : '?') . 'page={=page}';
        }
        $urlPattern  = $query;
        $currentPage = $_REQUEST['page'] ?? 1;
        $totalPages  = (0 === $recordsPerPage ? 0 : (int) \ceil($totalCount / $recordsPerPage));

        if ($totalPages && $currentPage > $totalPages) {
            $currentPage = $totalPages;
        }
        $offset     = ($currentPage - 1) * $recordsPerPage;
        $pagination = Pagination::getHtml($totalCount, $currentPage, $recordsPerPage, $pagesPerBlock, $urlPattern);

        return [$pagination, $offset, $recordsPerPage, $totalPages];
    }
}

/*

$totalCount     = 1232;
$recordsPerPage = 50;
$pagesPerBlock  = 9;
$currentPage    = $request->getQuery('page');
$urlPattern     = '/attraction/?page={=page}';

// html
$pagingHtml  = pagination::getHtml($totalCount, $currentPage, $recordsPerPage, $pagesPerBlock, $urlPattern);

view::assign([
    'pagingHtml'  => $pagingHtml,
]);
*/

/*
// array, define
$paging = pagination::get($totalCount, $currentPage, $recordsPerPage, $pagesPerBlock, $urlPattern, true);

view::assign([
    'paging'  => $paging,
]);

view::define([
    'pagination' => 'theme/service/normal/pagination.phtml',
]);
*/

// theme/service/normal/pagination.phtml
/*

<nav style='text-align:center'>

    <ul class="pagination">
        {?pagination.prevUrl}
            <li><a href="{=pagination.prevUrl}">&laquo;</a></li>
        {:}
            <li class='disabled'><a>&laquo;</a></li>
        {/}

        {@page = pagination.pages}
            {?page.url}
                <li {?page.isCurrent}class="active"{/}>
                    <a href="{=page.url}">{=page.num}</a>
                </li>
            {:}
                <li class="disabled"><span>{=page.num}</span></li>
            {/}
        {/}

        {?pagination.nextUrl}
            <li><a href="{=pagination.nextUrl}">&raquo;</a></li>
        {:}
            <li class='disabled'><a>&raquo;</a></li>
        {/}
    </ul>

</nav>

*/
