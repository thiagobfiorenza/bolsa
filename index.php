<?php 
if(date('w') >=1 && date('w') <=5) {
	$db = mysql_connect($mysql_host,$mysql_user,$mysql_password) or die("Database error");
	mysql_select_db($mysql_database, $db);

	$query = 'SELECT 1 FROM bolsa WHERE created = "'.date('Y-m-d').'";';
	$result = mysql_query($query);
	if(mysql_num_rows($result) == 0) {
		$query = 'INSERT INTO bolsa (created) VALUES ("'.date('Y-m-d').'");';
		$result = mysql_query($query);
		$id_bolsa = mysql_insert_id();
		
		$url = 'http://www.infomoney.com.br/ibovespa/cotacoes';
		$header = file_get_contents($url);
		$inicio = strpos($header, '<marquee');
		$fim = strpos($header, '</marquee>');
		$length = ($fim - $inicio) + 10;
		
		$acoes = substr($header,$inicio,$length);
		$quantasAcoes = substr_count($acoes, "title='");
		$inicio = 0;
		for($i=0; $i<$quantasAcoes; $i++){
			$inicio = $inicio + strpos(substr($acoes, $inicio), "title='")+7;
			$fim = strpos(substr($acoes,$inicio), "'");
			$dados[$i]['nome'] = substr($acoes,$inicio,$fim);
			
			$inicioCod = $inicio + $fim + strpos(substr($acoes,$inicio+$fim), ">")+1;
			$fimCod = strpos(substr($acoes,$inicioCod), "&nbsp;&nbsp;");
			$dados[$i]['cod'] = substr($acoes,$inicioCod,$fimCod);
			
			$inicioValor = $inicioCod + $fimCod + 12;
			$fimValor = strpos(substr($acoes,$inicioValor), "&nbsp;&nbsp;");
			$dados[$i]['valor'] = str_replace(',','.',substr($acoes,$inicioValor,$fimValor));
		}
	
		foreach($dados as $acao){
			$query = 'INSERT INTO bolsa_valores (cod, nome, valor, id_bolsa) VALUES ("'.$acao['cod'].'","'.$acao['nome'].'","'.$acao['valor'].'","'.$id_bolsa.'");';
			$result = mysql_query($query); 
		
		}
		echo "Sincronizado!";
	}
	$query = 'SELECT * FROM bolsa_valores GROUP BY cod;';
	$result = mysql_query($query);
	if(mysql_num_rows($result) != 0) {
?>
<table border="1">
	<thead>
		<tr>
			<th>Código</th>
			<th>Nome</th>
		</tr>
	</thead>
	<tbody>
<?php 
		while($acoes = mysql_fetch_assoc($result)){
?>
		<tr>
			<td><?php echo $acoes['cod']; ?></td>
			<td><?php echo $acoes['nome']; ?></td>
<?php
		$query = 'SELECT valor FROM bolsa_valores WHERE cod = "'.$acoes['cod'].'" ORDER BY id_bolsa_valores DESC;';
		$resultVal = mysql_query($query);
			if(mysql_num_rows($result) != 0) {
				$i = $ultimoValor = 0;
				while($valores = mysql_fetch_assoc($resultVal)){
					$valorFinal[$acoes['cod']][$i] = $valores['valor'];
					$i++;  
				
?>
			<td><?php echo $valores['valor']; ?></td>
<?php 
				}
?>
		</tr>
		<?php 
			}
		}
?>
	</tbody>
</table>
<?php
	}
}
?>
