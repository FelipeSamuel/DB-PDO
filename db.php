<?php
/**
* Classe criada para fazer consultas no banco de dados
* Autor: Felipe Samuel
* Data: 27/06/2017
*/
class DB
{
  private $dbUser;
  private $dbPass;
  private $dbHost;
  private $dbPort;
  private $dbDriver;
  private $dbName;
  private $dbCharset;

  private static $instance;

  function __construct()
  {
    // Arquivo config.json necessário na mesma pasta do arquivo db.php, pois
    // Ele contem as configurações usadas para conexao com o banco de dados
    $ponteiro = fopen ("config.json","r");
    $arquivo = "";

    while (!feof ($ponteiro)) {
      $arquivo .= fgets($ponteiro, 4096);
    }

    $json  = json_decode($arquivo);

    $this->dbUser = $json->db_user;
    $this->dbPass = $json->db_pass;
    $this->dbHost = $json->db_host;
    $this->dbPort = $json->db_port;
    $this->dbDriver = $json->db_driver;
    $this->dbName = $json->db_name;
    $this->dbCharset = $json->db_charset;

    fclose ($ponteiro);
  }

  private function connect()
  {
    if (!isset(self::$instance)) {
      try
      {
        switch ($this->dbDriver) {
          case 'mysql':
            self::$instance = new PDO($this->dbDriver.':host='.$this->dbHost.';dbname='.$this->dbName.';charset='.$this->dbCharset, $this->dbUser, $this->dbPass);
            break;
          default:
          //
          break;
        }

      }catch(Exception $e)
      {
        echo $e->getMessage();
      }
    }
    return self::$instance;
  }

  private function execute($query, $retornaId = false)
  {
    $pdo = $this->connect();

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    if($stmt->rowCount() > 0)
    {
      if($retornaId)
      {
        return $pdo->lastInsertId();
      }
    }else
    {
      return false;
    }
    return $stmt;
  }

  /**
  * $tabela = Nome da tabela para inserção, deve ser identica ao banco de dados
  *
  * $dados = ARRAY com os dados para inserção, sendo que a chave do array deve
  * ser identico ao nome do campo no banco de dados.
  *
  * $retornaId = caso seja passado como true, retorna o id do registro
  */
  public function insert($tabela,$dados, $retornaId = false)
  {
    $campos = implode(', ', array_keys($dados));
    //pega os valores do array, acrescenta aspas simples e virgula na separação
    $valores = "'".implode("', '", $dados)."'";
    //cria a query
    $query = "INSERT INTO {$tabela} ({$campos}) VALUES ({$valores})";
    //retorna se inseriu ou nao
    return $this->executa($query, $retornaId);
  }

  /*
  * $tabela = Nome da tabela para inserção, deve ser identica ao banco de dados
  *
  * $dados = ARRAY com os dados para a atualização, sendo que a chave do array deve
  * ser identico ao nome do campo no banco de dados. O.B.S: (Alguns campos não
  * precisam ser passados ).
  *
  * $condicao = recebe a condição para realizar a atualização. exemplo: "id = 4"
  *
  * $retornaId = caso seja passado como true, retorna o id do registro
  */
  public function update($tabela, array $dados, $condicao = null, $retornaId = false)
  {
    foreach ($dados as $chave => $valor) {//percorre o array recebido
      $campos[] = "{$chave} = '{$valor}'"; //atribui a chave do array(nome do campo no banco de dados) e concatena com o valor pra atualizar
    }
    $campos = implode(', ', $campos); //divide os campos por uma virgula
    $condicao = ($condicao) ? " WHERE {$condicao}" : null; //se existir uma condicao ele atribui, se nao ele modifica tudo da tabela
    $query = "UPDATE {$tabela} SET {$campos} {$condicao}"; //query usada pra atualizar os registros

    return $this->executa($query, $retornaId);
  }

  /*
  * $tabela = Nome da tabela para o select, deve ser identica ao banco de dados
  *
  * $campos = Campos que deseja trazer do banco de dados. ex: "nome, telefone"
  *
  * $condicao = Condição usada para trazer do banco. Ex: "WHERE nome = 'samuel'"
  * ou "LIKE pattern"
  *
  * Retorna um array Chave valor, com os dados do banco de dados
  */

  public function select($tabela, $campos = '*', $condicao = null)
  {
    //monta a query
    $query = "SELECT {$campos} FROM {$tabela} {$condicao}";
    //executa a query
    $result = $this->executa($query);
    if(is_bool($result)){//se o numero de linhas de retorno for igual a 0...
      if(!$result){
        return false;
      }
    }else{
      while($linha = $result->fetch(PDO::FETCH_OBJ)){ //transorma os dados do bd em um array
        $dados[] = $linha; // tribue os dados a outro array
      }
      return $dados;
    }
    return $result;
  }

  /*
  * $tabela = Nome da tabela PRINCIPAL para o select, deve ser identica ao banco de dados
  *
  *
  * array $tabelas = Array contento os nomes das tabelas para o inner join e os campos
  * de ligação, exemplo: "cachorro" => "cachorro.idcachorro = cliente.cachorro_id"
  *
  * $campos = Campos que deseja trazer do banco de dados. ex: "nome, telefone"
  *
  * $condicao = Condição usada para trazer do banco. Ex: "WHERE nome = 'samuel'"
  * ou "LIKE pattern"
  */
  public function innerJoin($tabela, array $tabelas, $campos = '*', $condicao = null)
  {
      $query = "SELECT {$campos} FROM {$tabela} {$condicao}";

      foreach ($tabelas as $key => $tab) {
        $query .= " INNER JOIN ".$key." ON ".$tab;
      }
      $result = $this->executa($query);

      if(is_bool($result)){//se o numero de linhas de retorno for igual a 0...
        if(!$result){
          return false;
        }
      }
      else{
        while($linha = $result->fetch(PDO::FETCH_OBJ)){ //transorma os dados do bd em um array
          $dados[] = $linha; // tribue os dados a outro array
        }
        return $dados;
      }
    }

  /*
  * $tabela = Nome da tabela para a remoção, deve ser identica ao banco de dados
  *
  * $campos = Campos que deseja trazer do banco de dados. ex: "nome, telefone"
  *
  * $condicao = Condição usada para excluir do banco. Ex: "WHERE id = 5"
  */
  public function delete($tabela, $condicao = null, $retornaId = false)
  {
    $condicao = ($condicao) ? " WHERE {$condicao}" : null;
    $query = "DELETE FROM {$tabela} {$condicao}";
    return $this->executa($query, $retornaId);
  }


}

?>
