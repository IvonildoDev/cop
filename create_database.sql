-- Criação do banco de dados para o Sistema COP
CREATE DATABASE IF NOT EXISTS controle_op;
USE controle_op;

-- Tabela de operações
CREATE TABLE IF NOT EXISTS operacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inicio_operacao DATETIME NOT NULL,
    km_inicial INT NOT NULL,
    nome_op_aux VARCHAR(255) NOT NULL,
    tipo_operacao VARCHAR(255) NOT NULL,
    nome_cidade VARCHAR(100) NOT NULL,
    nome_poco_serv VARCHAR(255) NOT NULL,
    nome_operador VARCHAR(255) NOT NULL,
    volume_bbl INT NOT NULL,
    temperatura INT NOT NULL,
    pressao INT NOT NULL,
    descricao_atividades TEXT,
    fim_operacao DATETIME,
    km_final INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de configurações do sistema
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_operador_principal VARCHAR(100) NOT NULL,
    nome_auxiliar VARCHAR(100) NOT NULL,
    nome_unidade VARCHAR(100) NOT NULL,
    placa_veiculo VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de deslocamentos
CREATE TABLE IF NOT EXISTS deslocamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origem VARCHAR(255) NOT NULL,
    destino VARCHAR(255) NOT NULL,
    km_inicial FLOAT NOT NULL,
    km_final FLOAT,
    inicio_deslocamento DATETIME NOT NULL,
    fim_deslocamento DATETIME,
    duracao_segundos INT,
    observacoes TEXT,
    operacao_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (operacao_id) REFERENCES operacoes(id) ON DELETE SET NULL
);

-- Tabela de aguardos
CREATE TABLE IF NOT EXISTS aguardos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inicio_aguardo DATETIME NOT NULL,
    fim_aguardo DATETIME,
    motivo TEXT NOT NULL,
    operacao_id INT,
    duracao_segundos INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (operacao_id) REFERENCES operacoes(id) ON DELETE SET NULL
);

-- Tabela de abastecimentos
CREATE TABLE IF NOT EXISTS abastecimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(20) NOT NULL,  -- 'agua' ou 'combustivel'
    inicio_abastecimento DATETIME NOT NULL,
    fim_abastecimento DATETIME,
    quantidade FLOAT,  -- em litros
    observacoes TEXT,
    duracao_segundos INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de refeições
CREATE TABLE IF NOT EXISTS refeicoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,  -- 'cafe', 'almoco', 'jantar', 'lanche'
    inicio_refeicao DATETIME NOT NULL,
    fim_refeicao DATETIME,
    local VARCHAR(100),
    observacoes TEXT,
    duracao_segundos INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de ocorrências
CREATE TABLE IF NOT EXISTS ocorrencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    descricao TEXT,
    data_ocorrencia DATETIME NOT NULL,
    localizacao TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    status ENUM('registrada', 'em_andamento', 'resolvida', 'cancelada') DEFAULT 'registrada',
    operacao_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (operacao_id) REFERENCES operacoes(id) ON DELETE SET NULL
);

-- Tabela de mobilizações
CREATE TABLE IF NOT EXISTS mobilizacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inicio_mobilizacao DATETIME NOT NULL,
    fim_mobilizacao DATETIME,
    tipo VARCHAR(50) NOT NULL,
    status ENUM('aguardando', 'ativo', 'concluido') DEFAULT 'aguardando',
    descricao TEXT,
    duracao_segundos INT,
    operacao_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (operacao_id) REFERENCES operacoes(id) ON DELETE SET NULL
);

-- Tabela de logs do sistema
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    descricao TEXT NOT NULL,
    usuario VARCHAR(100),
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    tabela_relacionada VARCHAR(50),
    registro_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de relatórios salvos
CREATE TABLE IF NOT EXISTS relatorios_salvos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    filtros TEXT,
    data_inicio DATE,
    data_fim DATE,
    formato VARCHAR(10) DEFAULT 'pdf',
    criado_por VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir configuração padrão se não existir
INSERT INTO configuracoes (nome_operador_principal, nome_auxiliar, nome_unidade, placa_veiculo)
SELECT '', '', '', ''
FROM DUAL
WHERE NOT EXISTS (SELECT * FROM configuracoes LIMIT 1);

-- Adicionar índices para melhorar performance
CREATE INDEX idx_deslocamentos_inicio ON deslocamentos(inicio_deslocamento);
CREATE INDEX idx_aguardos_inicio ON aguardos(inicio_aguardo);
CREATE INDEX idx_abastecimentos_tipo ON abastecimentos(tipo);
CREATE INDEX idx_refeicoes_tipo ON refeicoes(tipo);
CREATE INDEX idx_operacoes_data ON operacoes(inicio_operacao);