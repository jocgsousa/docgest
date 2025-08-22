/**
 * Utilitários para formatação de datas
 */

/**
 * Formata uma data para o padrão brasileiro com tratamento robusto
 * @param {string|Date} dateString - Data a ser formatada
 * @param {Object} options - Opções de formatação (opcional)
 * @returns {string} Data formatada ou mensagem de erro
 */
export const formatDate = (dateString, options = {}) => {
  if (!dateString) return 'Data não disponível';
  
  try {
    // Converter formato MySQL (YYYY-MM-DD HH:mm:ss) para formato ISO
    let date;
    if (typeof dateString === 'string' && dateString.includes(' ') && !dateString.includes('T')) {
      // Formato MySQL: 2025-08-22 12:42:51
      date = new Date(dateString.replace(' ', 'T'));
    } else {
      // Outros formatos
      date = new Date(dateString);
    }
    
    // Verificar se a data é válida
    if (isNaN(date.getTime())) {
      return 'Data inválida';
    }
    
    // Opções padrão para formatação
    const defaultOptions = {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    };
    
    return date.toLocaleString('pt-BR', { ...defaultOptions, ...options });
  } catch (error) {
    console.error('Erro ao formatar data:', error, 'Data original:', dateString);
    return 'Data inválida';
  }
};

/**
 * Formata uma data apenas com dia, mês e ano
 * @param {string|Date} dateString - Data a ser formatada
 * @returns {string} Data formatada ou mensagem de erro
 */
export const formatDateOnly = (dateString) => {
  return formatDate(dateString, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};

/**
 * Formata uma data com horário completo
 * @param {string|Date} dateString - Data a ser formatada
 * @returns {string} Data formatada ou mensagem de erro
 */
export const formatDateTime = (dateString) => {
  return formatDate(dateString, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
};

/**
 * Formata uma data para exibição em tabelas (formato compacto)
 * @param {string|Date} dateString - Data a ser formatada
 * @returns {string} Data formatada ou mensagem de erro
 */
export const formatTableDate = (dateString) => {
  return formatDate(dateString, {
    day: '2-digit',
    month: '2-digit',
    year: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
};