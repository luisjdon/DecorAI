# 🏠 DecorAI – Sugestões Visuais com IA

DecorAI é uma aplicação web que permite aos usuários capturar fotos das quatro paredes de um ambiente e receber sugestões visuais geradas por IA para decoração ou reorganização, proporcionando uma análise completa e contextualizada do espaço.

## 📌 Funcionalidades

- Captura sequencial das 4 paredes do ambiente via dispositivo móvel
- Interface intuitiva com indicadores visuais das paredes capturadas
- Duas opções de sugestão:
  - Reorganizar o ambiente
  - Decorar com estilo e inspiração
- Análise individual de cada parede usando Gemini 1.5 Flash
- Geração de descrição integrada considerando todas as paredes
- Criação de imagens realistas panorâmicas com Stability AI
- Histórico de sugestões por sessão
- Exibição da descrição textual detalhada junto com a imagem gerada

## 🛠️ Tecnologias Utilizadas

- **Frontend**: HTML5, CSS, JavaScript
- **Backend**: PHP 7+
- **APIs**:
  - Gemini Pro Vision (Google)
  - Stability AI (Stable Diffusion)

## 🚀 Como Executar

1. Coloque os arquivos em um servidor web com suporte a PHP 7+
2. Certifique-se de que a pasta `imagens` tenha permissões de escrita
3. Acesse a aplicação através de um navegador web
4. Em dispositivos móveis, você poderá tirar fotos diretamente; em desktop, poderá fazer upload de imagens

## 🔐 Segurança

- As chaves de API estão expostas no código para fins de demonstração
- Em ambiente de produção, recomenda-se:
  - Armazenar as chaves de API em variáveis de ambiente
  - Implementar validação de extensões de imagem
  - Limitar o tamanho dos arquivos de upload

## 📦 Melhorias Implementadas

- ✅ Captura sequencial das 4 paredes para análise completa do ambiente
- ✅ Interface moderna e responsiva com fluxo de captura intuitivo
- ✅ Visualização em miniaturas das paredes capturadas
- ✅ Possibilidade de recapturar paredes específicas
- ✅ Análise individualizada de cada parede
- ✅ Geração de imagem panorâmica (16:9) para melhor visualização do ambiente
- ✅ Histórico de sugestões por sessão
- ✅ Texto explicativo detalhado junto da imagem (output do Gemini)
- ✅ Salvamento das imagens originais e geradas
- ✅ Logging para debug das respostas das APIs

## 📋 Estrutura de Arquivos

```
decorai/
├── index.html         ← Interface web/mobile
├── processar.php      ← Processa imagem + chama APIs
├── .htaccess          ← Força HTTPS
├── README.md          ← Documentação
└── imagens/           ← Pasta para salvar fotos
```
