<?php
// Suas chaves de API
$api_key_gemini = "AIzaSyAhtSTUL7Rz5ex1D_fEA6Gg77FiiBOdQDw"; // Gemini
$api_key_stability = "sk-AHqLmXkxjBYcoQf7tYxsuk5hQJiEy033kOGONHiEHaZYmTEc"; // Stability

// Verifica se a pasta de imagens existe
$pasta_imagens = __DIR__ . '/imagens';
if (!file_exists($pasta_imagens)) {
  mkdir($pasta_imagens, 0755, true);
}

// Inicializa variáveis
$acao = $_POST['acao'] ?? 'decorar';
$modo = $_POST['modo'] ?? 'single';
$timestamp = time();
$imagens_base64 = [];
$descricoes_paredes = [];

// Verifica o modo de operação
if ($modo === 'multi_paredes') {
  // Modo de múltiplas paredes
  $paredes_recebidas = 0;
  
  // Processa cada uma das 4 paredes
  for ($i = 1; $i <= 4; $i++) {
    if (isset($_FILES["imagem_{$i}"]) && $_FILES["imagem_{$i}"]["error"] == 0) {
      $paredes_recebidas++;
      
      // Salva a imagem original
      $imagem_tmp = $_FILES["imagem_{$i}"]["tmp_name"];
      $nome_arquivo_original = $pasta_imagens . "/{$timestamp}_parede_{$i}_original.jpg";
      move_uploaded_file($imagem_tmp, $nome_arquivo_original);
      
      // Adiciona ao array de imagens base64
      $imagens_base64[$i] = base64_encode(file_get_contents($nome_arquivo_original));
    }
  }
  
  // Verifica se recebeu pelo menos uma parede
  if ($paredes_recebidas == 0) {
    http_response_code(400);
    exit(json_encode(['erro' => 'Nenhuma imagem de parede recebida.']));
  }
  
  // Log das paredes recebidas
  file_put_contents($pasta_imagens . "/{$timestamp}_log_paredes.txt", "Paredes recebidas: {$paredes_recebidas}\n");
  
} else {
  // Modo de imagem única (compatibilidade com versão anterior)
  if (!isset($_FILES['imagem'])) {
    http_response_code(400);
    exit(json_encode(['erro' => 'Nenhuma imagem recebida.']));
  }
  
  // Processa a imagem única
  $imagem_tmp = $_FILES['imagem']['tmp_name'];
  $nome_arquivo_original = $pasta_imagens . "/{$timestamp}_original.jpg";
  move_uploaded_file($imagem_tmp, $nome_arquivo_original);
  
  // Adiciona ao array de imagens
  $imagens_base64[1] = base64_encode(file_get_contents($nome_arquivo_original));
}

// Função para processar uma imagem com o Gemini
function processarImagemGemini($imagem_base64, $acao, $numero_parede = null, $api_key_gemini, $pasta_imagens) {
  $instrucao = "";
  $timestamp = time();
  
  if ($numero_parede !== null) {
    $wall_names = [
      1 => "frente",
      2 => "direita",
      3 => "fundo",
      4 => "esquerda"
    ];
    
    $wall_name = $wall_names[$numero_parede] ?? "$numero_parede";
    
    if ($acao == 'decorar') {
      $instrucao = "Analise esta imagem da parede {$numero_parede} ({$wall_name}) de um ambiente interno. "
               . "Crie uma descrição detalhada de como decorar esta parede e área adjacente com estilo e inspiração. "
               . "Sugira cores, móveis, disposição, iluminação e elementos decorativos específicos para esta parede. "
               . "Seja específico e criativo, mantendo a descrição entre 50-80 palavras.";
    } else {
      $instrucao = "Analise esta imagem da parede {$numero_parede} ({$wall_name}) de um ambiente interno. "
               . "Crie uma descrição detalhada de como reorganizar esta área para melhor funcionalidade e estética. "
               . "Sugira nova disposição de móveis, organização de itens, e possíveis mudanças específicas para esta parede. "
               . "Seja específico e criativo, mantendo a descrição entre 50-80 palavras.";
    }
  } else {
    if ($acao == 'decorar') {
      $instrucao = "Analise esta imagem de um ambiente interno e crie uma descrição detalhada de como decorar este espaço com estilo e inspiração. "
               . "Sugira cores, móveis, disposição, iluminação e elementos decorativos que combinariam bem com o ambiente existente. "
               . "Seja específico e criativo, mantendo a descrição entre 100-150 palavras. "
               . "A descrição deve ser clara o suficiente para que um modelo de geração de imagens possa criar uma visualização realista do ambiente decorado.";
    } else {
      $instrucao = "Analise esta imagem de um ambiente interno e crie uma descrição detalhada de como reorganizar este espaço para melhor funcionalidade e estética. "
               . "Sugira nova disposição de móveis, organização de itens, e possíveis mudanças na distribuição do espaço. "
               . "Seja específico e criativo, mantendo a descrição entre 100-150 palavras. "
               . "A descrição deve ser clara o suficiente para que um modelo de geração de imagens possa criar uma visualização realista do ambiente reorganizado.";
    }
  }

  $prompt_gemini = [
    "contents" => [[
      "role" => "user",
      "parts" => [
        ["text" => $instrucao],
        ["inlineData" => [
          "mimeType" => "image/jpeg",
          "data" => $imagem_base64
        ]]
      ]
    ]]
  ];

  $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$api_key_gemini");
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($prompt_gemini));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  $resposta_gemini = curl_exec($ch);
  curl_close($ch);

  // Log da resposta do Gemini para debug
  $log_filename = $numero_parede !== null ? 
    "{$pasta_imagens}/gemini_response_parede_{$numero_parede}_{$timestamp}.json" : 
    "{$pasta_imagens}/gemini_response_{$timestamp}.json";
  file_put_contents($log_filename, $resposta_gemini);

  $dados = json_decode($resposta_gemini, true);
  
  // Verificar se a resposta contém o conteúdo esperado
  if (isset($dados['candidates'][0]['content']['parts'][0]['text'])) {
    return $dados['candidates'][0]['content']['parts'][0]['text'];
  } else {
    // Fallback se a resposta do Gemini não for como esperado
    return $acao == 'decorar' ? 
      "Um ambiente elegante e bem decorado com cores harmoniosas, móveis modernos e iluminação aconchegante para a parede {$numero_parede}." : 
      "Um ambiente bem organizado com móveis dispostos de forma funcional, espaços otimizados e fluxo de circulação melhorado para a parede {$numero_parede}.";
  }
}

// Processar as imagens com o Gemini
if ($modo === 'multi_paredes') {
  // Processar cada parede individualmente
  foreach ($imagens_base64 as $numero_parede => $imagem) {
    $descricoes_paredes[$numero_parede] = processarImagemGemini($imagem, $acao, $numero_parede, $api_key_gemini, $pasta_imagens);
  }
  
  // Criar uma descrição combinada de todas as paredes
  $descricao_combinada = "Baseado na análise das quatro paredes do ambiente:\n\n";
  
  foreach ($descricoes_paredes as $numero_parede => $descricao) {
    $wall_names = [
      1 => "Parede da frente",
      2 => "Parede da direita",
      3 => "Parede do fundo",
      4 => "Parede da esquerda"
    ];
    $wall_name = $wall_names[$numero_parede] ?? "Parede $numero_parede";
    $descricao_combinada .= "$wall_name: $descricao\n\n";
  }
  
  // Adicionar uma conclusão
  if ($acao == 'decorar') {
    $descricao_combinada .= "Visão geral: Transforme este ambiente com uma decoração harmoniosa que integre todas as paredes em um conceito único e coeso, mantendo a funcionalidade e adicionando estilo.";
  } else {
    $descricao_combinada .= "Visão geral: Reorganize este ambiente para melhorar o fluxo, a funcionalidade e o aproveitamento do espaço, criando uma disposição mais eficiente e agradável.";
  }
  
  $prompt_texto = $descricao_combinada;
  
} else {
  // Modo de imagem única
  $prompt_texto = processarImagemGemini($imagens_base64[1], $acao, null, $api_key_gemini, $pasta_imagens);
}

// Criar um prompt para a Stability AI
if ($modo === 'multi_paredes') {
  // Versão resumida do prompt para a Stability AI
  $prompt_resumido = "";
  
  if ($acao == 'decorar') {
    $prompt_resumido = "Renderização 3D fotorrealista de um ambiente interno completo após decoração profissional. ";
    $prompt_resumido .= "O ambiente inclui: ";
    
    // Adicionar elementos específicos de cada parede
    foreach ($descricoes_paredes as $numero_parede => $descricao) {
      // Extrair as primeiras 20 palavras de cada descrição
      $palavras = explode(' ', $descricao);
      $palavras_resumidas = array_slice($palavras, 0, 20);
      $descricao_resumida = implode(' ', $palavras_resumidas);
      
      $wall_names = [
        1 => "na parede frontal",
        2 => "na parede direita",
        3 => "na parede do fundo",
        4 => "na parede esquerda"
      ];
      $wall_name = $wall_names[$numero_parede] ?? "na parede $numero_parede";
      
      $prompt_resumido .= "$descricao_resumida $wall_name; ";
    }
    
    $prompt_resumido .= "Design de interiores moderno e elegante, iluminação natural, cores harmoniosas.";
  } else {
    $prompt_resumido = "Renderização 3D fotorrealista de um ambiente interno completamente reorganizado para melhor funcionalidade. ";
    $prompt_resumido .= "O ambiente inclui: ";
    
    // Adicionar elementos específicos de cada parede
    foreach ($descricoes_paredes as $numero_parede => $descricao) {
      // Extrair as primeiras 20 palavras de cada descrição
      $palavras = explode(' ', $descricao);
      $palavras_resumidas = array_slice($palavras, 0, 20);
      $descricao_resumida = implode(' ', $palavras_resumidas);
      
      $wall_names = [
        1 => "na área frontal",
        2 => "na área direita",
        3 => "na área do fundo",
        4 => "na área esquerda"
      ];
      $wall_name = $wall_names[$numero_parede] ?? "na área $numero_parede";
      
      $prompt_resumido .= "$descricao_resumida $wall_name; ";
    }
    
    $prompt_resumido .= "Espaço otimizado, fluxo de circulação melhorado, organização eficiente.";
  }
  
  $prompt_stability = $prompt_resumido;
} else {
  // Modo de imagem única
  $prefixo_stability = "Fotografia realista de um ambiente interno, ";
  $prompt_stability = $prefixo_stability . $prompt_texto;
}

// Log do prompt final para a Stability AI
file_put_contents($pasta_imagens . "/stability_prompt_{$timestamp}.txt", $prompt_stability);

// 2. Envia prompt para a Stability AI
$stability_data = [
  'prompt' => $prompt_stability,
  'output_format' => 'png',
  'aspect_ratio' => '16:9', // Formato panorâmico para visualizar melhor o ambiente completo
  'style_preset' => 'photographic',
  'samples' => 1
];

$ch = curl_init('https://api.stability.ai/v2beta/stable-image/generate/core');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Authorization: Bearer ' . $api_key_stability,
  'Content-Type: application/json',
  'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stability_data));

$resposta_stability = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log da resposta da Stability para debug
file_put_contents($pasta_imagens . "/stability_response_{$timestamp}.json", $resposta_stability);

// Processar resposta da Stability AI
$stability_json = json_decode($resposta_stability, true);
$imagem_gerada_base64 = '';

if ($http_code == 200 && isset($stability_json['artifacts'][0]['base64'])) {
  $imagem_gerada_base64 = $stability_json['artifacts'][0]['base64'];
  
  // Salvar imagem gerada
  $nome_arquivo_gerado = $pasta_imagens . "/{$timestamp}_gerado.png";
  file_put_contents($nome_arquivo_gerado, base64_decode($imagem_gerada_base64));
} else {
  // Log de erro
  file_put_contents($pasta_imagens . "/erro_stability_{$timestamp}.txt", 
    "HTTP Code: $http_code\nResposta: " . $resposta_stability);
  
  // Retornar erro
  http_response_code(500);
  exit(json_encode(['erro' => 'Falha ao gerar imagem.']));
}

// Retornar dados como JSON
header('Content-Type: application/json');
echo json_encode([
  'imagem' => $imagem_gerada_base64,
  'descricao' => $prompt_texto
]);
