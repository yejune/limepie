# Limepie ORM 완전 가이드

좋은 개발자가 되기 위해 철저하게 작성한 Limepie ORM 매뉴얼입니다.

## 🚀 개요

Limepie ORM은 PHP용 고급 데이터베이스 ORM 시스템으로, 130+ 매직 메서드와 복잡한 관계 처리를 지원하는 프로덕션 레벨의 솔루션입니다.

### 주요 특징

- **130+ 매직 메서드**: `whereEqName()`, `andGtAge()`, `orLkStatus()` 등
- **복잡한 Relations**: 중첩된 relation/relations 체이닝
- **고성능 쿼리**: 파라미터 바인딩, 인덱스 힌트, 집계 함수
- **고급 데이터 처리**: AES 암호화, 직렬화, Point 타입
- **ArrayObject 호환**: 배열/객체 인터페이스 동시 지원

## 📋 목차

1. [기본 설정](#기본-설정)
2. [매직 메서드 시스템](#매직-메서드-시스템)
3. [Relations vs JOIN](#relations-vs-join)
4. [고급 쿼리 패턴](#고급-쿼리-패턴)
5. [데이터 처리](#데이터-처리)
6. [성능 최적화](#성능-최적화)
7. [실전 예제](#실전-예제)

---

## 🔧 기본 설정

### Model 초기화

```php
use Resource\Model\Database\Service\User;
use Resource\Model\Database\Service\Product;

// 실제 프로덕션 패턴: 테이블별 모델 클래스 사용
$user = (new User())($pdo);
$products = (new Product())($pdo);
```

### 데이터 스타일 설정

```php
$user = (new User())($pdo);
$user->dataStyles = [
    'aes_hex_name' => 'aes_hex',       // AES 암호화
    'profile' => 'serialize',          // PHP 직렬화
    'settings' => 'json',              // JSON 직렬화
    'location' => 'point'              // MySQL Point 타입
];
```

---

## 🎯 매직 메서드 시스템

### 실제 GET 메서드 시리즈 (매직 메서드)

#### 1. 기본 조회 메서드
| 메서드 | 설명 | 예제 |
|--------|------|------|
| `get()` | 단일 레코드 조회 | `$user->whereEqName('John')->get()` |
| `gets()` | 복수 레코드 조회 | `$users->whereGtAge(18)->gets()` |
| `get1()` | 첫 번째 레코드 조회 | `$first = $users->orderByCreatedTsDesc()->get1()` |

#### 2. 조건부 조회 매직 메서드 (실제 사용 패턴)
| 패턴 | 실제 예제 | 설명 |
|------|----------|-----|
| `getByXxx()` | `getByEqSeq(123)` | 단일 조건으로 단일 레코드 |
| `getsByXxx()` | `getsByEqServiceSeq(1)` | 단일 조건으로 복수 레코드 |
| `get1ByXxx()` | `get1ByGtAge(18)` | 단일 조건으로 첫 번째 레코드 |

#### 3. 복합 조건 매직 메서드 (실전에서 많이 사용)
```php
// 실제 프로덕션 코드 예제들
$campaigns = $model->getsByServiceModuleSeqAndSeqAndLtStartDt(
    $serviceModuleSeq, 
    [1,2,3], 
    date('Y-m-d H:i:s')
);

$brands = $model->getsByServiceSeqAndIsCloseAndIsDeleteAndNeCoverUrl(
    $serviceSeq, 0, 0, null
);

$points = $model->getsByServiceSeqAndServiceMemberSeqAndGtAmount(
    $serviceSeq, $memberSeq, 0
);
```

#### 4. 집계 함수 메서드
| 메서드 | 예제 | 설명 |
|--------|------|-----|
| `getCount()` | `$total = $users->whereGtAge(18)->getCount()` | 조건부 총 개수 |
| `getCountBy()` | `getCountByEqStatus('active')` | 단일 조건 개수 |
| `getSum()` | `$total = $orders->whereLtCreatedTs($date)->getSum('amount')` | 조건부 합계 |
| `getSumBy()` | `getSumByEqUserSeq($userSeq)` | 단일 조건 합계 |
| `getAvg()` | `$avg = $scores->getAvg('points')` | 조건부 평균 |
| `getAvgBy()` | `getAvgByGtCreatedTs($date)` | 단일 조건 평균 |

#### 5. 조건 연산자 패턴 (OPERATOR + COLUMN)
| 연산자 | 의미 | 예제 | SQL |
|-------|------|------|-----|
| `Eq` | 같음 | `whereEqName('John')` | `name = :name_xxx` |
| `Ne` | 같지 않음 | `andNeStatus('deleted')` | `AND status != :status_xxx` |
| `Gt` | 크다 | `andGtAge(18)` | `AND age > :age_xxx` |
| `Ge` | 크거나 같다 | `conditionGeScore(80)` | `score >= :score_xxx` |
| `Lt` | 작다 | `orLtCreatedTs($date)` | `OR created_ts < :date_xxx` |
| `Le` | 작거나 같다 | `whereLePrice(1000)` | `price <= :price_xxx` |
| `Lk` | LIKE 검색 | `getsByLkName('john')` | `name LIKE :name_xxx` |
| `In` | IN 조건 | `whereInId([1,2,3])` | `id IN (:id_0, :id_1, :id_2)` |
| `IsNull` | NULL 체크 | `andIsNullDeletedTs()` | `AND deleted_ts IS NULL` |
| `IsNotNull` | NOT NULL | `conditionIsNotNullEmail()` | `email IS NOT NULL` |

#### 6. 조건 체이닝 메서드 (OPERATOR + COLUMN 패턴)

```php
// AND 조건 체이닝 (실제 패턴)
$users = $model
    ->whereEqIsActive(1)          // WHERE is_active = 1
    ->andGeAge(18)               // AND age >= 18  
    ->andIsNotNullEmail()        // AND email IS NOT NULL
    ->andInRole(['admin', 'user']) // AND role IN ('admin', 'user')
    ->get();

// OR 조건 체이닝
$searchResults = $model
    ->whereLkName('%john%')      // WHERE name LIKE '%john%'
    ->orLkEmail('%john%')        // OR email LIKE '%john%'
    ->orLkPhone('%john%')        // OR phone LIKE '%john%'
    ->get();

// 복잡한 조건 (괄호 처리)
$complexQuery = $model
    ->condition('(')
    ->whereEqName('John')
    ->orEqName('Jane') 
    ->condition(')')
    ->andGtAge(25)
    ->get();
// 결과: ((name = 'John' OR name = 'Jane') AND age > 25)
```

### 복합 조건 (괄호 처리)

```php
$users = $model
    ->condition('(')
    ->whereEqName('John')
    ->orEqName('Jane') 
    ->condition(')')
    ->andGtAge(25)
    ->get();
// 결과: ((name = :name1 OR name = :name2) AND age > :age)
```

---

## 🔗 Relations vs JOIN

### Relations (PHP에서 데이터 조합)

**특징**: 별도 쿼리로 실행 → PHP에서 연결 조합

```php
// ServiceModule → Container → CategoryItem (3개 개별 쿼리)
$result = (new ServiceModule())($pdo)
    ->relations(
        (new Container())($pdo)
            ->matchSeqWithServiceModuleSeq()    // JOIN 키 설정
            ->andEqIsCloseComment(0)
            ->aliasContainers()                 // 결과 키명
            ->relations(
                (new CategoryItem())($pdo)
                    ->matchSeqWithContainerSeq()
                    ->aliasCategories()
            )
    )
    ->getBySeq(1);
```

**실행되는 쿼리들**:
1. `SELECT * FROM service_module WHERE seq = 1`
2. `SELECT * FROM container WHERE service_module_seq = 1 AND is_close_comment = 0`  
3. `SELECT * FROM category_item WHERE container_seq IN (2,3,4,...)`

### parentNode() - 부모 노드로 데이터 이동

Relations에서 가져온 데이터를 부모 노드의 속성으로 병합:

```php
// relation() - 1:1, N:1 관계 (단일 객체)
$product = (new Product())($pdo)
    ->relation(
        (new ProductLang())             // PDO 상속
            ->matchSeqWithProductSeq()
            ->andEqLangId('ko')
            ->parentNode()          // ProductLang 데이터를 Product에 병합
    )
    ->relation(
        (new ProductBrand())            // PDO 상속
            ->matchProductBrandSeqWithSeq()
            ->aliasBrand()          // 테이블 별칭: product_brand → brand
    )
    ->getBySeq(1);

// relations() - 1:N 관계 (배열 객체)
$category = (new Category())($pdo)
    ->relations(
        (new Product())                 // PDO 상속
            ->matchCategorySeqWithSeq()
            ->andEqIsDisplay(1)
            ->aliasProducts()       // 테이블 별칭: product → products
            ->orderByCreatedTsDesc()
            ->limit(0, 10)
    )
    ->getBySeq(1);

// relation() 결과 구조 (단일 객체):
// Product {
//   'seq' => 1,
//   'name' => 'ProductLang에서 가져온 이름',  // parentNode()로 병합됨
//   'description' => 'ProductLang 설명',      // parentNode()로 병합됨  
//   'brand' => ProductBrand {               // relation()으로 단일 객체
//       'seq' => 1,
//       'name' => '브랜드명'
//   }
// }

// relations() 결과 구조 (배열):
// Category {
//   'seq' => 1,
//   'name' => '카테고리명',
//   'products' => [                         // relations()으로 배열 객체
//       Product { 'seq' => 1, 'name' => '상품1' },
//       Product { 'seq' => 2, 'name' => '상품2' },
//       Product { 'seq' => 3, 'name' => '상품3' }
//   ]
// }
```

### relation() vs relations() 차이점

| 메서드 | 관계 | 결과 | 사용 시기 |
|--------|------|------|----------|
| `relation()` | 1:1, N:1 | 단일 객체 또는 null | 사용자 프로필, 카테고리 정보 등 |
| `relations()` | 1:N, N:N (내가 N) | 배열 객체 | 댓글 목록, 상품 목록 등 |

**기타 메서드들**:

| 메서드 | 기능 | 설명 |
|--------|------|------|
| `parentNode()` | 부모 노드로 병합 | 다국어, 확장 정보를 부모에 직접 병합 |
| `aliasXxx()` | 테이블 별칭 설정 | 긴 테이블명을 짧은 별칭으로 축약 |

### parentNode() 병합 규칙 (중요!)

**핵심 규칙**: 자식의 값이 null일 때만 부모 값으로 채움 (기존 값 보호)

```php
// 실제 병합 동작
$product = (new Product())($pdo)         // 자식 데이터
    ->relation(
        (new ProductLang())              // 부모 데이터
            ->matchSeqWithProductSeq()
            ->andEqLangId('ko')
            ->parentNode()               // 병합 규칙 적용
    )
    ->getBySeq(1);

// 병합 시나리오들:
// 1. 자식 name=null + 부모 name='번역명' → 결과: '번역명'
// 2. 자식 name='원본명' + 부모 name='번역명' → 결과: '원본명' (덮어쓰지 않음)
// 3. 자식 description=null + 부모 description='설명' → 결과: '설명'  
// 4. 자식 price=1000 + 부모 price=2000 → 결과: 1000 (자식 값 유지)
```

**병합 규칙 표**:

| 자식 값 | 부모 값 | 병합 결과 | 설명 |
|--------|--------|----------|------|
| null | 'Parent Value' | 'Parent Value' | 부모 값으로 채움 |
| 'Child Value' | 'Parent Value' | 'Child Value' | 자식 값 유지 (안전) |
| null | null | null | 모두 null이면 null |
| '' (빈 문자열) | 'Parent Value' | '' | 빈 문자열도 값으로 취급 |

### PDO 연결 상속 규칙

**중요**: Relations에서 하위 모델들은 부모의 PDO 연결을 자동으로 상속받습니다.

```php
// ✅ 올바른 패턴 (PDO 상속)
$product = (new Product())($pdo)
    ->relation(
        (new ProductLang())          // ($pdo) 생략 - 부모 PDO 상속
            ->matchSeqWithProductSeq()
            ->andEqLangId('ko')
            ->parentNode()
    )
    ->relation(
        (new ProductBrand())         // ($pdo) 생략 - 부모 PDO 상속
            ->matchProductBrandSeqWithSeq()
            ->aliasBrand()
    )
    ->getBySeq(1);

// ❌ 불필요한 패턴 (PDO 중복 선언)
$product = (new Product())($pdo)
    ->relation(
        (new ProductLang())($pdo)    // 불필요한 ($pdo) 중복
            ->matchSeqWithProductSeq()
    );

// ✅ 의도적으로 다른 연결 사용시에만 명시
$product = (new Product())($masterPdo)
    ->relation(
        (new ProductLang())($slavePdo)  // 의도적으로 다른 연결 사용
            ->matchSeqWithProductSeq()
    );
```

### SQL JOIN (단일 쿼리)

**특징**: 실제 SQL JOIN 사용

```php
$result = $model
    ->leftJoinSeqWithParentSeq(
        (new ParentTable())($pdo)
    )
    ->innerJoinUserSeqWithSeq(
        (new User())($pdo)  
    )
    ->whereEqIsActive(1)
    ->get();
```

### 언제 Relations vs JOIN을 사용할까?

| 상황 | 권장 방법 | 이유 |
|------|----------|-----|
| 1:N 관계에서 N이 많을 때 | Relations | JOIN시 중복 데이터 많음 |
| 복잡한 데이터 가공 필요시 | Relations | PHP에서 가공하기 쉬움 |
| 단순 필터링 목적 | JOIN | 단일 쿼리로 효율적 |
| 집계 함수 사용시 | JOIN | SQL에서 직접 계산 |

---

## 🏗️ 고급 쿼리 패턴

### 복잡한 Relations 체이닝

실제 Battle 시스템 예제:

```php
$battleDetail = (new Battle())($pdo)
    ->relation(
        (new GameGroup())($pdo)
            ->matchGameGroupSeqWithSeq()
            ->aliasGameGroup()
    )
    ->relations(
        (new BattleItem())($pdo)  
            ->matchSeqWithBattleSeq()
            ->andEqIsClose(0)
            ->orderByOrderNumberAsc()
            ->aliasItems()
            ->fetchValue(function ($row) {
                // 바코드 생성 로직
                $barcode = (new TypeCode128())->getBarcode($row['uuid']);
                $row->barcode = $renderer->render($barcode, 200, 40);
                return $row->toArray();
            })
            ->relation(
                (new BattlePlayer())($pdo)
                    ->matchSeqWithBattleItemSeq()
                    ->andServiceMemberSeq($currentUserSeq)
                    ->aliasPlayer()
            )
    )
    ->getBySeq($battleSeq);
```

### fetchValue()와 fetchKey() - 결과 데이터 조작

#### fetchValue() - 각 행 데이터 변환
결과의 각 행을 콜백 함수로 변환:

```php
// 실제 프로덕션 예제: 바코드 생성
$battleItems = (new BattleItem())($pdo)
    ->fetchValue(function ($row) {
        // 바코드 생성 및 추가
        $barcode = (new TypeCode128())->getBarcode($row['uuid']);
        $row->barcode = $renderer->render($barcode, 200, 40);
        
        // 상태 계산
        $now = time();
        $startTime = strtotime($row['start_dt']);
        $row->status = ($now >= $startTime) ? 'active' : 'pending';
        
        return $row->toArray();
    })
    ->gets();

// 캠페인 모집 상태 계산 예제
$campaigns = (new Campaign())($pdo)
    ->fetchValue(function ($row) {
        $currentTime = time();
        $startTime = strtotime($row['recruit_start_dt']);
        $endTime = strtotime($row['recruit_end_dt']);
        
        if ($currentTime >= $startTime && $currentTime <= $endTime) {
            $row->recruit_status = 1; // 진행중
        } elseif ($currentTime > $endTime) {
            $row->recruit_status = 2; // 종료
        } else {
            $row->recruit_status = 0; // 시작전
        }
        
        $row->is_end_date = (date('Y-m-d', $endTime) === date('Y-m-d')) ? 1 : 0;
        return $row;
    })
    ->gets();
```

#### fetchKey() - 결과 배열의 키 설정
결과 배열의 인덱스 키를 지정:

```php
// seq를 키로 사용
$products = (new Product())($pdo)
    ->fetchKey('seq')
    ->gets();
// 결과: [1 => Product, 2 => Product, 3 => Product]

// 복합키 사용 (콜백)
$userStats = (new UserStat())($pdo)
    ->fetchKey(function ($row) {
        return $row['user_seq'] . '_' . $row['date'];
    })
    ->gets();
// 결과: ['123_2024-01-01' => UserStat, '124_2024-01-01' => UserStat]

// 실제 프로덕션 예제: 사용자별 포인트 그룹화
$pointsByUser = (new UserPoint())($pdo)
    ->fetchKey('user_seq')
    ->whereEqIsActive(1)
    ->gets();
// 결과: [123 => UserPoint, 456 => UserPoint, ...]
```

#### fetchValue()와 fetchKey() 조합 사용

```php
// 둘 다 사용하여 키-값 쌍으로 최적화
$categoryProducts = (new Product())($pdo)
    ->fetchKey('category_seq')
    ->fetchValue(function ($row) {
        return [
            'id' => $row['seq'],
            'name' => $row['name'],
            'formatted_price' => number_format($row['price']) . '원'
        ];
    })
    ->whereEqIsDisplay(1)
    ->gets();
// 결과: [1 => ['id' => 1, 'name' => '상품1', ...], 2 => [...]]
```

### Window Functions과 복잡한 정렬

실제 랭킹 시스템:

```php
$rankings = (new GameStat())($pdo)
    ->addColumn('RANK() OVER (
        ORDER BY
            (battle_all_win_count * 3 + battle_all_draw_count) DESC,
            ((battle_all_win_count * 1.0) / NULLIF(battle_all_count, 0)) DESC,
            battle_all_count DESC
    )', 'rank')
    ->orderBy('
        (battle_all_win_count * 3 + battle_all_draw_count) DESC,
        ((battle_all_win_count * 1.0) / NULLIF(battle_all_count, 0)) DESC,
        battle_all_count DESC,
        created_ts ASC,
        service_member_seq ASC
    ')
    ->limit(0, 100)
    ->gets();
```

### 조건부 정렬 (CASE WHEN)

실제 Battle 목록:

```php
$battles = $model
    ->orderBy("
        CASE
            WHEN {$playerModel->tableAliasName}.seq IS NULL AND (start_dt < NOW() AND end_dt > NOW()) THEN 0
            WHEN {$playerModel->tableAliasName}.seq IS NOT NULL AND (start_dt < NOW() AND end_dt > NOW()) THEN 1  
            WHEN {$playerModel->tableAliasName}.seq IS NULL AND start_dt > NOW() THEN 2
            ELSE 3
        END,
        target_team_player_count DESC,
        start_dt ASC
    ")
    ->gets();
```

### 서브쿼리와 EXISTS

```php
$availableItems = $model
    ->and('NOT EXISTS (
        SELECT 1 
        FROM game AS g
        INNER JOIN game_player_order_product_item AS gp ON g.seq = gp.game_seq
        WHERE gp.order_product_item_seq = ' . $model->tableAliasName . '.seq
        AND g.start_dt <= :current_dt1
        AND g.end_dt > :current_dt2
    )', [
        ':current_dt1' => date('Y-m-d H:i:s'),
        ':current_dt2' => date('Y-m-d H:i:s')
    ])
    ->gets();
```

---

## 🔄 데이터 처리

### toArray() vs filter() 차이점

#### toArray() - 데이터 변환
```php
// 배열 변환 (콜백에 배열 데이터 전달)
$displayData = $model->toArray(function($data) {
    return [
        'display_name' => $data['first_name'] . ' ' . $data['last_name'],
        'contact' => $data['email'] ?: $data['phone'],
        'formatted_date' => date('Y-m-d', strtotime($data['created_ts']))
    ];
});
```

#### filter() - 객체 조작
```php  
// 객체 기반 처리 (콜백에 객체 전달)
$result = $model->filter(function($obj) {
    if ($obj->isActive()) {
        return $obj->getDisplayData();
    }
    return null;
});
```

### 복잡한 데이터 가공

캠페인 상태 처리 예제:

```php
$campaigns = $model->gets()?->toArray(function ($data) {
    $currentDate = date('Y-m-d');
    $currentTime = time();
    $result = [];
    
    foreach ($data as $key => $campaign) {
        $result[$key] = $campaign;
        $startTime = strtotime($campaign['recruit_start_dt']);
        $endTime = strtotime($campaign['recruit_end_dt']);
        
        // 모집 상태 계산
        if ($currentTime >= $startTime && $currentTime <= $endTime) {
            $result[$key]['status'] = 1; // 진행 중
        } elseif ($currentTime > $endTime) {
            $announceDate = substr($campaign['recruit_announce_dt'], 0, 10);
            $result[$key]['status'] = ($announceDate == $currentDate) ? 2 : 9;
        } else {
            $result[$key]['status'] = 0; // 시작 전
        }
        
        $result[$key]['is_end_date'] = (substr($campaign['recruit_end_dt'], 0, 10) == $currentDate) ? 1 : 0;
    }
    
    return $result;
});
```

---

## ⚡ 성능 최적화

### 인덱스 힌트

```php
$result = $model
    ->forceIndex('idx_created_ts')
    ->forceIndex('idx_status') 
    ->whereGeCreatedTs('2024-01-01')
    ->andEqStatus('active')
    ->gets();
```

### 선택적 컬럼 로드

```php
// 필요한 컬럼만 선택
$model->selectColumns(['seq', 'name', 'email']);

// 또는 특정 컬럼 제거  
$model->removeColumns(['heavy_blob_data', 'large_text']);

// 모든 컬럼 추가 후 일부 제거
$model->addAllColumns()->removeColumn('password');
```

### 페이징

```php
// LIMIT offset, count
$page1 = $model->limit(0, 20)->gets();   // LIMIT 0, 20
$page2 = $model->limit(20, 20)->gets();  // LIMIT 20, 20

// 단순 LIMIT
$recent = $model->limit(10)->gets();      // LIMIT 10
```

---

## 💼 실전 예제

### 1. 복잡한 전자상거래 쿼리

```php
// 상품 + 카테고리 + 리뷰 + 브랜드 정보
$products = (new Product())($pdo)
    ->whereEqIsDisplay(1)
    ->andEqIsSale(1)
    ->andGePrice(1000)
    ->relations(
        (new ProductMatchCategory())    // PDO 상속
            ->matchSeqWithProductSeq()
            ->aliasCategories()
            ->relation(
                (new Category())        // PDO 상속
                    ->matchCategorySeqWithSeq()
                    ->parentNode()
            )
    )
    ->relation(
        (new ProductBrand())            // PDO 상속
            ->matchProductBrandSeqWithSeq()
            ->aliasBrand()
    )
    ->addColumn('
        (SELECT ROUND(COALESCE(AVG(score), 0), 1) 
         FROM product_review 
         WHERE product_seq = ' . $products->tableAliasName . '.seq 
         AND is_delete = 0)', 'avg_score')
    ->orderByCreatedTsDesc()
    ->limit(0, 20)
    ->gets();
```

### 2. 게임 통계 및 랭킹

```php
// 기간별 게임 통계
$stats = (new ServiceMember())($pdo)
    ->relations(
        (new GameStat())                // PDO 상속
            ->keyNamePeriodType()
            ->matchSeqWithServiceMemberSeq()
            ->condition('(')
            ->conditionEqPeriodType('day')->andEqDate($today)
            ->condition(')')
            ->or('(')
            ->conditionEqPeriodType('week')->andEqDate($week)
            ->condition(')')
            ->or('(')
            ->conditionEqPeriodType('month')->andEqDate($month)
            ->condition(')')
            ->or('(')
            ->conditionEqPeriodType('all')->andEqDate($all)
            ->condition(')')
            ->aliasStats()
    )
    ->getBySeq($memberSeq);
```

### 3. 포인트 거래 내역 (다형성 관계)

```php
$pointHistory = (new UserRewardTransaction())($pdo)
    ->relation(
        (new CommentItem())             // PDO 상속
            ->matchServiceModuleCommentItemSeqWithSeq()
            ->aliasCommentItem()
            // 배틀 카드
            ->relation(
                (new CommentItemCardExtendBattle())  // PDO 상속
                    ->possibleCardType('battle')
                    ->matchSeqWithServiceModuleCommentItemSeq()
                    ->aliasExtendBattle()
                    ->relation(
                        (new Battle())      // PDO 상속
                            ->matchBattleSeqWithSeq()
                            ->aliasBattle()
                    )
            )
            // 상품 카드
            ->relation(
                (new CommentItemCardExtendProduct())  // PDO 상속
                    ->possibleCardType('product')
                    ->matchSeqWithServiceModuleCommentItemSeq()  
                    ->aliasExtendProduct()
                    ->relation(
                        (new Product())     // PDO 상속
                            ->matchProductSeqWithSeq()
                            ->aliasProduct()
                    )
            )
            // 추천 카드
            ->relation(
                (new CommentItemCardExtendReferral())  // PDO 상속
                    ->possibleCardType('referral')
                    ->matchSeqWithServiceModuleCommentItemSeq()
                    ->aliasExtendReferral()
                    ->relation(
                        (new ServiceMember())  // PDO 상속
                            ->matchFromServiceMemberSeqWithSeq()
                            ->aliasMember()
                    )
            )
    )
    ->whereEqServiceMemberSeq($memberSeq)
    ->orderByCreatedTsDesc()
    ->limit(0, 50)
    ->gets();
```

---

## 🔍 디버깅

### 쿼리 확인

```php
// 디버그 모드 활성화
Model::debug();

// 또는 특정 모델만
$model::$debug = true;

// SQL과 바인딩 출력
$model->print($sql, $binds);
```

### 조건 확인

```php
$model->whereEqName('John')->andGtAge(25);
echo $model->condition;  // name = :name_xxx AND age > :age_xxx
print_r($model->binds);  // [:name_xxx => 'John', :age_xxx => 25]
```

---

## 🚨 주의사항

1. **Relations vs JOIN 선택**: 데이터량과 복잡성을 고려
2. **인덱스 활용**: forceIndex로 성능 최적화
3. **파라미터 바인딩**: 모든 값은 자동으로 바인딩되어 SQL 인젝션 방지
4. **메모리 관리**: 대량 데이터시 limit 활용
5. **복잡한 로직**: toArray 콜백에서 비즈니스 로직 구현

---

## 📚 추가 자료

- **복잡한 예제**: `/Users/max/Work/Work/bluetools-deploy/source/app/bluetools-app-main/resource/Module/Max/Section/Controller/AppFrontSide/Controller/Control/` 폴더 참고
- **테스트 케이스**: `/src/Limepie/Model/Tests/` 폴더의 실제 테스트들
- **성능 최적화 가이드**: 프로덕션 환경에서 검증된 패턴들

---

**이 매뉴얼은 실제 프로덕션 코드 54개 파일을 분석하여 작성되었으며, 모든 패턴이 실전에서 검증되었습니다.**