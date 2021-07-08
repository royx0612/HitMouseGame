<?php
date_default_timezone_set('Asia/Taipei');
/*
  資料庫類別
  功能:建構連線，提供查詢語法
  方法:
    1. 設定資料庫連線:
      (1) $conn = new Database();
          $conn->connect($username, $password, $dbName, $host);
      (2) 或者 $conn = new Database($username, $password, $dbName, $host);
    2. 類別主要功能
      (1) 查詢:
        A. 設置 select 欄位: setSelect(array $columns)
        B. 設置 where 條件: setWhere($column, $method, $value)
        C. 設置 orderby 條件: setOrderBy([column => value, ...])
        D. 設置 limit 條件: setLimit(int $start, int $length)
        E. 執行: execute('select')
        F. 取得結果:
          (A) fetchAll()
          (B) fetch()
      (2) 新增:
        A. 設置參數: setCreate(array $data)
        B. 執行: execute('create')
      (3) 修改:
        A. 設置參數: setUpdate(array $data, array $conditions, $method)
        B. 設置額外條件: setWhere($column, $method, $value)
        C. 執行: execute('update')
      (4) 刪除:
        A. 設置參數: setDelete(array $conditions, $method)
        B. 設置額外條件: setWhere($column, $method, $value)
        C. 執行: execute('delete')
    3. 其他功能:
      (1) 取得資料表所有行數
        A. 設置: setRowTotalCount()
        B. 設置額外條件: setWhere($column, $method, $value)
        C. 執行: execute('other')
        D. 取得結果: fetch()
      (2) 清空資料表
        A. 設置資料表 setTable($tableName)
        B. 執行: truncateTable()
      (3) 客製化查詢
        A. 設置 : customQuery(string $sql, array $data)
        B. 取得結果:
          (A) fetchAll()
          (B) fetch()
  其他:
    1. 錯誤產生時，拋出例外 Exception 
    2. 本類別不具 join 等功能，僅提供快捷功能，詳情請參閱函式方法
    


  【範例功能】
    ### 建立與資料庫的連線
    $conn = new Database($username, $password, $dbName, $host);
    
    ### 進行資料查詢 
    $conn->setWhere('ID', '=', '21'); // 設定條件參數 AND
    $conn->setWhereOr('storeName', 'like', '%店%'); //設定條件參數 Or
    $conn->setMultipleWhere([ // 設定多筆條件參數 AND
      ['ID', '=', '21'],
      ['storeName', 'like', '店']
    ]);
    $conn->setOrderBy(['ID' => 'DESC', 'storeName' => 'ASC']); // 設定排序
    $conn->setLimit(0, 10); // 設定取回的筆數
    $conn->execute('select'); // 查詢動作
    $result = $conn->fetchAll(); // 取得資料
    print_r($result);

    ### 取得行數
    $conn->clearParams();
    $conn->setRowTotalCount();
    $conn->setWhere('storeName', 'like', '%店%');
    $result = $conn->fetch();
    print_r($result['count']);

    ### 新增資料
    $data = [
      'storeName' => '耀耀店',
      'area' => '中區',
      'money' => 1234567,
      'people' => 12000
    ];
    $conn->setCreate($data);
    $conn->execute('create');

    ### 刪除資料
    $deleteConditions = [
      ['ID', '=', '126'],
      ['area', '=', '中區']
    ];
    $conn->setDelete($deleteConditions, 'AND'); // 第二參數 OR，可改條件匹配方式
    $conn->execute('delete');

    ### 修改資料
    $updateData = [
      'storeName' => '修改過的 西瓜店',
      'area' => '外島',
      'money' => 500000,
      'people' => 7000
    ];
    $updateConditions = [
      ['ID', '=',  125]
    ];

    $conn->setUpdate($updateData, $updateConditions, 'AND');
    $conn->execute('update');

    ### 清空資料表
    $conn->setTable('crud');
    $conn->truncateTable();

    ### 客製化查詢
    // SQL 準備
    $sql = ""INSERT INTO `$tableName`
      (ID,storeName,area,money,people) VALUES
      (NULL,'泰山店','北區','100000','100'),
      (NULL,'新莊店','北區','100000','100')";
    $conn->customQuery($sql); // 客製化查詢
    $rowCount = $conn->getEffectedRowCount(); // 新增幾列(數字型態)
*/
class Database
{
  // 連線參數， ERROR_MODE 回報錯誤；ERROR_VALUES 丟出例外
  const ERROR_MODE = PDO::ATTR_ERRMODE,
    ERROR_VALUES = PDO::ERRMODE_EXCEPTION;


  private $dbHandler, // 資料庫連線
    $tableName; // sql 語法


  private $selectStatement = "*", // Select 欄位
    $insertStatement, // insert 欄位
    $insertValues = [], // insert 條件
    $updateStatement, // update 欄位
    $updateValues = [], // update 條件
    $deleteStatement, // delete 欄位
    $deleteValues = [], // delete 條件
    $orderByStatement,  // 動態 OrderBy
    $whereAndStatement = [], // where AND 語法
    $whereAndValues = [],  // where AND 條件
    $whereOrStatement = [], // where OR 語法
    $whereOrValues = [],  // where AND 條件
    $limitStatement; // limit 語法

  private $fetchAllDatas = [],  // 查詢資料回傳
    $whereParamNumber = 1,  // Where 條件參數編號
    $rowTotalCount, // 配合 setRowTotalCount 查詢總筆數
    $effectedRowCount; // 執行結果受影響的資料數

  /******* 環境功能 Start ********/
  ### 建構子需要三個參數，一個 Optional ( $host)，用來產生 $dbHandler
  function __construct(string $username = null, string $password = null, string $dbName = null, string $host = "localhost")
  {
    // 是否有輸入參數
    if (func_num_args() > 1) {
      $this->connect($username, $password, $dbName, $host);
    }
  }

  ### 連線資料庫並取得 dbHandler
  function connect(string $username, string $password, string $dbName, string $host = "localhost")
  {
    try {
      $dsn = sprintf("mysql:host=%s; dbname=%s; charset=utf8;", $host, $dbName);
      $this->dbHandler = new PDO($dsn, $username, $password);
      // set the PDO error mode to exception
      if (!$this->dbHandler->setAttribute(self::ERROR_MODE, self::ERROR_VALUES)) {
        throw new PDOException("Set Connection Attribute Faild.");
      }

      // echo "Connected successfully";
    } catch (PDOException $e) {
      echo "Connection failed: " . $e->getMessage();
    }
  }

  ### 設置 TableName
  public function setTable(string $tableName): bool
  {
    try {
      // 尚未連線，彈出錯誤訊息
      $this->checkValidConnection();

      //查詢資料表使否存在，沒錯誤回傳 true ，有錯誤直接丟出例外
      $this->dbHandler->query("SELECT 1 FROM `$tableName` LIMIT 1");

      // 設置類別 tableName
      $this->tableName = $tableName;

      return true;
    } catch (Exception $e) {
      die("Error: " . $e->getMessage());
    }
  }

  /******* 環境功能 END ********/


  /******* 執行功能 Start ********/
  ### 執行查詢
  public function execute($action): void
  {
    try {
      // 尚未連線，彈出錯誤訊息
      $this->checkValidConnection();
      switch ($action) {
          // 查詢
        case 'select':
        case 'other':
          // Select 資料查詢
          $selectArray = $this->generateSelectSqlQuery();
          $sql = $selectArray['sql'];
          $values = $selectArray['values'];
          $stmt = $this->dbHandler->prepare($sql);
          $stmt->execute($values);
          // 將結果回傳到成員
          $this->fetchAllDatas = $stmt->fetchAll(PDO::FETCH_ASSOC);

          $this->effectedRowCount = $stmt->rowCount();
          break;

          // 新增
        case 'create':
          $sql = $this->insertStatement;
          $values = $this->insertValues;
          $stmt = $this->dbHandler->prepare($sql);
          $stmt->execute($values);

          // 回傳新增筆數
          $this->effectedRowCount = $stmt->rowCount();
          break;

          // 更新
        case 'update':
          $updateArray = $this->generateUpdateSqlQuery();
          $sql = $updateArray['sql'];
          // 更新資料
          $dataValues = $this->updateValues;
          // where 條件
          $whereValues = $updateArray['values'];
          $values = array_merge($dataValues, $whereValues);
          $stmt = $this->dbHandler->prepare($sql);
          $stmt->execute($values);

          // 回傳更新筆數
          $this->effectedRowCount = $stmt->rowCount();
          break;

          // 刪除
        case 'delete':
          $deleteArray = $this->generateDeleteSqlQuery();
          $sql = $deleteArray['sql'];
          $values = $deleteArray['values'];
          $stmt = $this->dbHandler->prepare($sql);
          $stmt->execute($values);

          // 回傳刪除筆數
          $this->effectedRowCount = $stmt->rowCount();
          break;

          // 其他參數報錯
        default:
          throw new Exception("Unknow Execute opration.(未知的執行代碼 " . $action . " )");
      }
    } catch (Exception $e) {

      die("Error: " . $e->getMessage());
    }
  }

  ### 回傳單一值
  public function fetch(): array
  {
    // 如果沒有結果就執行
    if (!isset($this->fetchAllDatas) || count($this->fetchAllDatas) == 0) {
      $this->execute('select');
    }

    // 將資料複製一份
    $this->fetchDatas = $this->fetchAllDatas;
    // 將結果回傳
    if (count($this->fetchDatas) > 0) {
      return array_shift($this->fetchDatas);
    } else {
      return [];
    }
  }

  ### 取得所有資料內容
  public function fetchAll(): array
  {
    // 如果沒有結果就執行
    if (!isset($this->fetchAllDatas) || count($this->fetchAllDatas) == 0) {
      $this->execute('select');
    }


    // 回傳內容
    return  $this->fetchAllDatas;
  }
  /******* 執行功能 End ********/


  /******* 主要功能 Start ********/

  ### 選擇欄位，可配合 where 查詢
  public function setSelect(array $columns): bool
  {
    try {
      // 尚未連線，彈出錯誤訊息
      $this->checkValidConnection();
      ### 查詢欄位使否存在，有錯誤直接丟出例外
      // 是否設定資料表名稱
      if (empty($this->tableName)) {
        throw new Exception("Please set table name first.(請先設定表格名稱)");
      }

      # 檢查所有欄位是否在資料表中
      $checkColumnsResult = $this->checkColumnsInTable($columns);
      if ($checkColumnsResult['result'] == false) {
        throw new Exception($checkColumnsResult['msg']);
      }

      $selectStatement = sprintf("%s", implode(', ', $columns));

      $this->selectStatement = $selectStatement;
    } catch (Exception $e) {

      die("Error: " . $e->getMessage());
    }
    return true;
  }

  ### 新增單筆資料
  public function setCreate(array $data): bool
  {
    try {
      // 尚未連線，彈出錯誤訊息
      $this->checkValidConnection();
      ### 查詢欄位使否存在，有錯誤直接丟出例外
      // 是否設定資料表名稱
      if (empty($this->tableName)) {
        throw new Exception("Please set table name first.(請先設定表格名稱)");
      }

      # 檢查所有欄位是否在資料表中
      $columns = array_keys($data);
      $checkColumnsResult = $this->checkColumnsInTable($columns);
      if ($checkColumnsResult['result'] == false) {
        throw new Exception($checkColumnsResult['msg']);
      }

      # 產生 Insert Sql
      // 產生 array keys
      $insertColumns = array_keys($data);
      $insertColumnsWithExecute = array_map(fn ($c) => ':' . $c, $insertColumns);
      $insertStatement = sprintf(
        "INSERT INTO `%s` (%s) VALUES(%s)",
        $this->tableName,
        implode(', ', $insertColumns),
        // implode(', ', array_fill(0, count($data), '?'))
        implode(', ', $insertColumnsWithExecute)
      );

      // 放入類別屬性
      $this->insertStatement = $insertStatement;
      // 輸入的資料，鍵值被加上 : ，用來在 execute 時放入作為參數
      // 用 array_combin 將兩個陣列結合為一個完整的資料 keys + values
      $this->insertValues = array_combine($insertColumnsWithExecute, array_values($data));
    } catch (Exception $e) {

      die("Error: " . $e->getMessage());
    }
    return true;
  }

  ### 新增多筆資料
  public function setMultipleCreate(array $datas): bool
  {
    foreach ($datas as $data) {
      $this->setCreate($data);
    }
    return true;
  }

  ### 刪除功能，可配合 where 查詢
  public function setDelete(array $conditions = [], string $method = 'AND'): bool
  {
    try {
      // 尚未連線，彈出錯誤訊息
      $this->checkValidConnection();

      // 有輸入條件，直接設定到 where AND 
      if ($conditions && strtoupper($method) == 'AND') {
        $this->setMultipleWhere($conditions);
      } elseif ($conditions && strtoupper($method) == 'OR') {
        $this->setMultipleWhereOr($conditions);
      } else {
        throw new Exception("Wrong method in Set Delete Config.(given " . $method . ")");
      }

      // 放入類別屬性
      $deleteStatement = sprintf("DELETE FROM `%s`", $this->tableName);
      $this->deleteStatement = $deleteStatement;
    } catch (Exception $e) {

      die("Error: " . $e->getMessage());
    }
    return true;
  }

  ### 更新資料，可配合 where 查詢
  public function setUpdate(array $data, array $conditions, string $method = 'AND'): bool
  {
    try {
      // 尚未連線，彈出錯誤訊息
      $this->checkValidConnection();
      ### 查詢欄位使否存在，有錯誤直接丟出例外
      // 是否設定資料表名稱
      if (empty($this->tableName)) {
        throw new Exception("Please set table name first.(請先設定表格名稱)");
      }

      # 檢查所有欄位是否在資料表中
      $columns = array_keys($data);
      $checkColumnsResult = $this->checkColumnsInTable($columns);
      if ($checkColumnsResult['result'] == false) {
        throw new Exception($checkColumnsResult['msg']);
      }

      // 有輸入條件，直接設定到 where AND 
      if ($conditions && strtoupper($method) == 'AND') {
        $this->setMultipleWhere($conditions);
      } elseif ($conditions && strtoupper($method) == 'OR') {
        $this->setMultipleWhereOr($conditions);
      } else {
        throw new Exception("Wrong method in Set Delete Config.(given " . $method . ")");
      }

      # 產生 Update Sql
      $updateColumns = array_map(fn ($c) => ':' . $c, array_keys($data));
      // 產生 array ['ID=:ID', 'name=:name', ...]
      $updateColumnsWithExecute = array_map(fn ($c) => sprintf("%s=%s", $c, ':' . $c), array_keys($data));
      $updateStatement = sprintf("%s", implode(', ', $updateColumnsWithExecute));

      // 放入類別屬性
      $this->updateStatement = $updateStatement;
      // 輸入的資料，鍵值被加上 : ，用來在 execute 時放入作為參數
      // 用 array_combin 將兩個陣列結合為一個完整的資料 keys + values
      $this->updateValues = array_combine($updateColumns, array_values($data));


      return true;
    } catch (Exception $e) {

      die("Error: " . $e->getMessage());
    }
  }

  ## 取得資料表行數，可配合 where 查詢
  public function setRowTotalCount(): bool
  {
    $this->selectStatement = 'count(*) as count';
    return true;
  }

  ### 清除資料表
  public function truncateTable(): bool
  {
    try {
      // 尚未連線，彈出錯誤訊息
      $this->checkValidConnection();
      $sql = sprintf("TRUNCATE TABLE `%s`", $this->tableName);
      $stmt = $this->dbHandler->prepare($sql);
      $stmt->execute();
    } catch (Exception $e) {

      die("Error: " . $e->getMessage());
    }
    return true;
  }

  ### 客製化查詢
  public function customQuery(string $sql, array $data = [])
  {
    try {
      // 尚未連線，彈出錯誤訊息
      $this->checkValidConnection();
      $stmt = $this->dbHandler->prepare($sql);
      $stmt->execute($data);
      $this->e2ffectedRowCount = $stmt->rowCount();
      //儲存結果
      $this->fetchAllDatas = $stmt->fetchAll();
    } catch (Exception $e) {

      die("Error: " . $e->getMessage());
    }
  }
  /******* 主要功能 End ********/


  /******* 條件功能 Start ********/
  ### 設定 Where 條件，條件若有多個之間以 And 串再一起
  public function setWhere(string $column, string $method, string $value, bool $AND = true): bool
  {
    try {
      // 尚未連線，彈出錯誤訊息
      $this->checkValidConnection();
      ### 查詢欄位使否存在，有錯誤直接丟出例外
      // 是否設定資料表名稱
      if (empty($this->tableName)) {
        throw new Exception("Please set table name first.(請先設定表格名稱)");
      }
      # 檢查欄位是否在資料表中
      $checkColumnsResult = $this->checkColumnInTable($column);
      if ($checkColumnsResult['result'] == false) {
        throw new Exception($checkColumnsResult['msg']);
      }

      ### 依照 $AND 判斷SQL語法放到哪一個屬性內
      $variableName = ($AND) ? "whereAndStatement" : "whereOrStatement";
      $variableValue = ($AND) ? "whereAndValues" : "whereOrValues";

      ### 產生 Where Statement sql 語法
      $whereParamName = sprintf(":param%s", $this->whereParamNumber++);
      $$variableName = sprintf("`%s` %s %s", $column, $method, $whereParamName);

      // 新增至類別成員
      $this->$variableName[] = $$variableName;
      $this->$variableValue[$whereParamName] = $value;
    } catch (Exception $e) {

      die("Error: " . $e->getMessage());
    }
    return true;
  }

  ### 設定 Where 條件，條件若有多個之間以 And 串再一起
  public function setWhereOr(string $column, string $method, string $value): bool
  {
    return $this->setWhere($column, $method, $value, false);
  }

  ### 多重設定 Where 條件
  public function setMultipleWhere(array $conditions, $AND = true): bool
  {
    try {
      ### 檢查所有內容是否正確
      array_map(function ($condition) {
        if (count($condition) != 3) {
          throw new Exception(sprintf("Params Numbers in Multiple Where Statement are wrong, should be equal 3 .(given %s)", count($condition)));
        }
      }, $conditions);

      ### 設定 where 條件
      foreach ($conditions as $condition) {
        $this->setWhere($condition[0], $condition[1], $condition[2], $AND);
      }
    } catch (Exception $e) {

      die("Error: " . $e->getMessage());
    }
    return true;
  }

  ### 多重設定 Where 條件
  public function setMultipleWhereOr(array $conditions): bool
  {
    return $this->setMultipleWhere($conditions, false);
  }

  ### 設定 Limit
  public function setLimit(int $start, int $length): void
  {
    $this->limitStatement = sprintf("LIMIT %s, %s", $start, $length);
  }

  ### 設定排序
  public function setOrderBy(array $columns): bool
  {
    try {
      // 尚未連線，彈出錯誤訊息
      $this->checkValidConnection();
      ### 查詢欄位使否存在，有錯誤直接丟出例外
      // 是否設定資料表名稱
      if (empty($this->tableName)) {
        throw new Exception("Please set table name first.(請先設定表格名稱)");
      }
      # 檢查所有欄位是否在資料表中
      $checkColumnsResult = $this->checkColumnsInTable($columns);
      if ($checkColumnsResult['result'] == false) {
        throw new Exception($checkColumnsResult['msg']);
      }

      ### 產生 Orderby sql 語法
      // 用 array_walk 將 $columns 的 key(column) 跟 value(ASC or DESC) 結合後取代 value 的值
      array_walk($columns, fn (&$v, $c) => $v = sprintf("%s %s", $c, $v));
      // 將每一列的 value 用 , 結合變成字串
      $this->orderByStatement = implode(', ', array_values($columns));
    } catch (Exception $e) {
      die("Error: " . $e->getMessage());
    }
    return true;
  }
  /******* 條件功能 End ********/

  ### 取得資料總比數
  public function getRowTotalCount(): int
  {
    // 取得第一列資料
    $row = $this->fetch();
    return (empty($row)) ? 0 : $row['count'];
  }

  ### 清除條件查詢及結果參數
  public function clearParams(array $types = ['ALL']): void
  {
    foreach ($types as $type) {
      if ($type == 'ALL') {
        $this->selectStatement = '*';
        $this->orderByStatement = null;
        $this->whereAndStatement = [];
        $this->whereAndValues = [];
        $this->whereOrStatement = [];
        $this->whereOrValues = [];
        $this->limitStatement = null;
      } elseif ($type == 'select') {
        $this->selectStatement = '*';
      } elseif ($type == 'where') {
        $this->whereAndStatement = [];
        $this->whereAndValues = [];
        $this->whereOrStatement = [];
        $this->whereOrValues = [];
      } elseif ($type == 'orderby') {
        $this->orderByStatement = null;
      } elseif ($type == 'limit') {
        $this->limitStatement = null;
      }
    }
    $this->fetchAllDatas = [];  // 查詢資料回傳
  }

  ### 取得受影響的資料筆數
  public function getEffectedRowCount()
  {
    return $this->effectedRowCount;
  }
  ### 產生 Select 語法
  private function generateSelectSqlQuery()
  {
    ### 產生完整 SQL 語法
    $sql = sprintf("SELECT %s FROM `%s`", $this->selectStatement, $this->tableName);

    // 產生 where sql 語法
    $whereStatementArray = $this->generatorWhereStatement();
    $whereStatement = $whereStatementArray['sql']; // 取得 sql 語法
    $values = $whereStatementArray['values']; // 取得 where 的變數內容
    if (isset($whereStatement) && $whereStatement) {
      $sql .= " WHERE " . $whereStatement;
    }
    # 設定 order 語法
    if ($this->orderByStatement) {
      $sql .= " ORDER BY " . $this->orderByStatement;
    }

    # 設定 limit 條件
    if ($this->limitStatement) {
      $sql .= " " . $this->limitStatement;
    }

    return ['sql' => $sql, 'values' => $values];
  }

  ### 產生 Update 語法
  private function generateUpdateSqlQuery()
  {
    ### 產生 SQL 開頭
    $sql = sprintf("UPDATE `%s` SET ", $this->tableName);

    // 加入更新 SQL 語法 
    $sql .= $this->updateStatement;
    // 產生 where sql 語法
    $whereStatementArray = $this->generatorWhereStatement();
    $whereStatement = $whereStatementArray['sql']; // 取得 sql 語法
    $values = $whereStatementArray['values']; // 取得 where 的變數內容
    if (isset($whereStatement) && $whereStatement) {
      $sql .= " WHERE " . $whereStatement;
    }

    return ['sql' => $sql, 'values' => $values];
  }

  ### 產生 Delete 語法
  private function generateDeleteSqlQuery()
  {
    ### 產生完整 SQL 語法
    $sql = sprintf("DELETE FROM `%s`", $this->tableName);

    // 產生 where sql 語法
    $whereStatementArray = $this->generatorWhereStatement();
    $whereStatement = $whereStatementArray['sql']; // 取得 sql 語法
    $values = $whereStatementArray['values']; // 取得 where 的變數內容
    if (isset($whereStatement) && $whereStatement) {
      $sql .= " WHERE " . $whereStatement;
    }

    return ['sql' => $sql, 'values' => $values];
  }

  ### 結合所有的 where Statement
  private function generatorWhereStatement(): array
  {
    ### 是否有 Where 條件
    $clause = [];
    $values = [];

    # Where And 條件
    if (count($this->whereAndStatement)) {
      $whereAndStatement = implode(' AND ', $this->whereAndStatement);
      $clause[] = sprintf("(%s)", $whereAndStatement);
      $values = array_merge($values, $this->whereAndValues);
    }
    # Where Or 條件
    if (count($this->whereOrStatement)) {
      $whereOrStatement = implode(' OR ', $this->whereOrStatement);
      $clause[] = sprintf("(%s)", $whereOrStatement);
      $values = array_merge($values, $this->whereOrValues);
    }

    # AND where, OR where 條件結合
    $whereStatement = implode(' OR ', $clause);
    return ['sql' => $whereStatement, 'values' => $values];
  }

  ### 檢查當前連線，若無執行顯示錯誤並終止程式
  private function checkValidConnection(): bool
  {
    try {
      if (empty($this->dbHandler)) {
        throw new Exception("Connection Handler is Empty(尚未建立連線)");
      }
    } catch (Exception $e) {
      die("Error: " . $e->getMessage());
    }
    return true;
  }

  ### 檢查所有欄位是否在資料庫內
  private function checkColumnsInTable(array $columns): array
  {
    // 將欄位放入變數 $tableColumns
    $tableColumns = $this->getTableColumns();
    // 欄位是否存在
    array_map(function ($column) use ($tableColumns) {
      if (!in_array($column, $tableColumns)) {
        return ['result' => false, 'msg' => sprintf("column not found in Statement.(given %s)", $column)];
      }
    }, $columns);

    return ['result' => true, 'msg' => ''];
  }

  ### 檢查所有欄位是否在資料庫內
  private function checkColumnInTable(string $column): array
  {
    // 將欄位放入變數 $tableColumns
    $tableColumns = $this->getTableColumns();
    // 欄位是否存在
    if (!in_array($column, $tableColumns)) {
      return ['result' => false, 'msg' => sprintf("column not found in Statement.(given %s)", $column)];
    }

    return ['result' => true, 'msg' => ''];
  }

  ### 取得資料表欄位
  private function getTableColumns(): array
  {
    //查詢欄位使否存在，有錯誤直接丟出例外
    $stmt = $this->dbHandler->query("SHOW COLUMNS FROM `{$this->tableName}`");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ### 將欄位放入變數內
    $tableColumns = array_map(fn ($r) => $r['Field'], $result);
    return $tableColumns;
  }
}
### 除錯測試
function dd($variable)
{
  die(sprintf("<pre>%s</pre>", var_export($variable, true)));
}