<?php
header("Content-Type: application/json; charset=UTF-8");
require_once("db.php");


//Instanciando o Objeto DB
$db  = new DB();

/**
*   Pegando registros simples do banco
*/

$clientes = $db->select('cliente');
echo json_encode($clientes);

/**
*   Pegando registros complexos do banco usando inner join
*/

//Criando um array com as tabelas secundarias e relacionando as chaves de cada tabela
$tabelasSecundarias = array("cachorro" => "cachorro.idcliente = cliente.idcliente");
$clientesECachorros = $db->innerJoin('cliente', $tabelasSecundarias);
echo json_encode($clientesECachorros);

/**
*  Inserindo no banco
*/

//Criando o arrray com o registro
$cliente = array("nome"=>"Felipe Samuel", "idade" = 17);
//Inserindo e esperando o id como retorno
$idDoCliente = $db->insert("cliente", $cliente, true);

/**
*  Inserindo no banco
*/

// Novos dados a serem atualizados
$cliente = array("nome"=>"Maria");
//Atualizando os registros que tiverem o id igual a quatro
$atualizado = $db->update("cliente", $cliente, "id = 4");

/**
*  Deletando do banco
*/
// Deletando o registro com o id igual a 4
$deletado = $db->delete("cliente", "id = 4");

?>
