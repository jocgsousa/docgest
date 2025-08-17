/**
 * Mapeamento de nomes de campos técnicos para nomes amigáveis
 * para exibição de mensagens de erro mais claras
 */

const fieldLabels = {
  // Campos comuns
  nome: 'Nome',
  email: 'Email',
  telefone: 'Telefone',
  endereco: 'Endereço',
  cidade: 'Cidade',
  estado: 'Estado',
  cep: 'CEP',
  
  // Campos de empresa
  cnpj: 'CNPJ',
  plano_id: 'Plano',
  data_vencimento: 'Data de Vencimento',
  
  // Campos de filial
  empresa_id: 'Empresa',
  inscricao_estadual: 'Inscrição Estadual',
  responsavel: 'Responsável',
  observacoes: 'Observações',
  status: 'Status',
  
  // Campos de usuário
  cpf: 'CPF',
  senha: 'Senha',
  tipo_usuario: 'Tipo de Usuário',
  filial_id: 'Filial',
  
  // Campos de plano
  preco: 'Preço',
  limite_usuarios: 'Limite de Usuários',
  limite_documentos: 'Limite de Documentos',
  limite_assinaturas: 'Limite de Assinaturas',
  descricao: 'Descrição',
  
  // Campos de documento
  titulo: 'Título',
  arquivo: 'Arquivo',
  documento_id: 'Documento',
  
  // Campos de assinatura
  signatario_nome: 'Nome do Signatário',
  signatario_email: 'Email do Signatário',
  signatario_cpf: 'CPF do Signatário',
  ordem: 'Ordem',
  token: 'Token',
  action: 'Ação'
};

/**
 * Função para obter o nome amigável de um campo
 * @param {string} fieldName - Nome técnico do campo
 * @returns {string} - Nome amigável do campo
 */
export const getFieldLabel = (fieldName) => {
  return fieldLabels[fieldName] || fieldName;
};

/**
 * Função para formatar mensagens de erro substituindo nomes de campos
 * @param {string} message - Mensagem de erro original
 * @param {string} fieldName - Nome técnico do campo
 * @returns {string} - Mensagem de erro formatada
 */
export const formatErrorMessage = (message, fieldName) => {
  const friendlyName = getFieldLabel(fieldName);
  
  // Se a mensagem já contém o nome amigável, retorna como está
  if (message.toLowerCase().includes(friendlyName.toLowerCase())) {
    return message;
  }
  
  // Substitui o nome técnico pelo nome amigável na mensagem
  const patterns = [
    new RegExp(`\\b${fieldName}\\b`, 'gi'),
    new RegExp(`O campo ${fieldName}`, 'gi'),
    new RegExp(`campo ${fieldName}`, 'gi')
  ];
  
  let formattedMessage = message;
  patterns.forEach(pattern => {
    formattedMessage = formattedMessage.replace(pattern, (match) => {
      if (match.toLowerCase().includes('o campo')) {
        return `O campo ${friendlyName}`;
      } else if (match.toLowerCase().includes('campo')) {
        return `campo ${friendlyName}`;
      }
      return friendlyName;
    });
  });
  
  return formattedMessage;
};

/**
 * Função para processar objeto de erros e formatar as mensagens
 * @param {Object} errors - Objeto de erros do backend
 * @returns {Object} - Objeto de erros com mensagens formatadas
 */
export const formatErrors = (errors) => {
  const formattedErrors = {};
  
  Object.keys(errors).forEach(fieldName => {
    const fieldErrors = errors[fieldName];
    
    if (Array.isArray(fieldErrors)) {
      formattedErrors[fieldName] = fieldErrors.map(error => 
        formatErrorMessage(error, fieldName)
      );
    } else {
      // Para strings simples, criar um array com a mensagem formatada
      const formatted = formatErrorMessage(fieldErrors, fieldName);
      formattedErrors[fieldName] = [formatted]; // Sempre retornar como array
    }
  });
  
  return formattedErrors;
};

export default fieldLabels;