import React, { useState } from 'react';
import styled from 'styled-components';
import { useNavigate } from 'react-router-dom';
import Button from '../components/Button';
import Input from '../components/Input';
import LandingHeader from '../components/layout/LandingHeader';
import { Mail, Phone, MapPin, Clock, Send, CheckCircle } from 'lucide-react';
import api from '../services/api';

const Container = styled.div`
  min-height: 100vh;
  background-color: ${props => props.theme.colors.white};
`;

const HeroSection = styled.section`
  padding: 4rem 1.5rem;
  text-align: center;
  background: linear-gradient(135deg, ${props => props.theme.colors.gray[50]} 0%, ${props => props.theme.colors.white} 100%);
`;

const HeroContent = styled.div`
  max-width: 4xl;
  margin: 0 auto;
`;

const HeroTitle = styled.h1`
  font-size: 3rem;
  font-weight: 800;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 1.5rem;
  line-height: 1.1;
  
  @media (max-width: 768px) {
    font-size: 2.25rem;
  }
`;

const HeroSubtitle = styled.p`
  font-size: 1.25rem;
  color: ${props => props.theme.colors.gray[600]};
  margin-bottom: 2.5rem;
  max-width: 3xl;
  margin-left: auto;
  margin-right: auto;
  line-height: 1.6;
`;

const ContactSection = styled.section`
  padding: 4rem 1.5rem;
  background-color: ${props => props.theme.colors.white};
`;

const SectionContainer = styled.div`
  max-width: 6xl;
  margin: 0 auto;
`;

const ContactGrid = styled.div`
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  
  @media (max-width: 1024px) {
    grid-template-columns: 1fr;
    gap: 3rem;
  }
`;

const ContactInfo = styled.div`
  display: flex;
  flex-direction: column;
  gap: 2rem;
`;

const InfoTitle = styled.h2`
  font-size: 2rem;
  font-weight: 700;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 1rem;
`;

const InfoDescription = styled.p`
  font-size: 1.125rem;
  color: ${props => props.theme.colors.gray[600]};
  line-height: 1.6;
  margin-bottom: 2rem;
`;

const InfoList = styled.div`
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
`;

const InfoItem = styled.div`
  display: flex;
  align-items: flex-start;
  gap: 1rem;
`;

const InfoIcon = styled.div`
  width: 3rem;
  height: 3rem;
  background-color: ${props => props.theme.colors.primary + '20'};
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: ${props => props.theme.colors.primary};
  flex-shrink: 0;
`;

const InfoContent = styled.div`
  flex: 1;
`;

const InfoLabel = styled.h3`
  font-size: 1.125rem;
  font-weight: 600;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 0.25rem;
`;

const InfoText = styled.p`
  color: ${props => props.theme.colors.gray[600]};
  line-height: 1.5;
`;

const ContactForm = styled.div`
  background-color: ${props => props.theme.colors.white};
  border: 1px solid ${props => props.theme.colors.gray[200]};
  border-radius: 1rem;
  padding: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
`;

const FormTitle = styled.h2`
  font-size: 1.5rem;
  font-weight: 700;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 1rem;
`;

const FormDescription = styled.p`
  color: ${props => props.theme.colors.gray[600]};
  margin-bottom: 2rem;
  line-height: 1.5;
`;

const Form = styled.form`
  display: flex;
  flex-direction: column;
  gap: 1rem;
`;

const FormRow = styled.div`
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  
  @media (max-width: 640px) {
    grid-template-columns: 1fr;
  }
`;

const TextArea = styled.textarea`
  width: 100%;
  min-height: 120px;
  padding: 0.75rem;
  border: 1px solid ${props => props.theme.colors.gray[300]};
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-family: inherit;
  resize: vertical;
  transition: border-color 0.2s ease-in-out;
  
  &:focus {
    outline: none;
    border-color: ${props => props.theme.colors.primary};
    box-shadow: 0 0 0 3px ${props => props.theme.colors.primary + '20'};
  }
  
  &::placeholder {
    color: ${props => props.theme.colors.gray[400]};
  }
`;

const ErrorMessage = styled.div`
  background-color: ${props => props.theme.colors.error + '20'};
  color: ${props => props.theme.colors.error};
  padding: 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  margin-bottom: 1rem;
`;

const SuccessMessage = styled.div`
  background-color: ${props => props.theme.colors.success + '20'};
  color: ${props => props.theme.colors.success};
  padding: 1rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
`;

const Footer = styled.footer`
  padding: 2rem 1.5rem;
  background-color: ${props => props.theme.colors.gray[900]};
  text-align: center;
`;

const FooterText = styled.p`
  color: ${props => props.theme.colors.gray[400]};
  font-size: 0.875rem;
`;

function Contato() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [formData, setFormData] = useState({
    nome: '',
    email: '',
    empresa: '',
    telefone: '',
    assunto: '',
    mensagem: ''
  });

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    setError('');
  };

  const validateForm = () => {
    if (!formData.nome || !formData.email || !formData.assunto || !formData.mensagem) {
      setError('Por favor, preencha todos os campos obrigatórios.');
      return false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.email)) {
      setError('Por favor, insira um e-mail válido.');
      return false;
    }

    return true;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess(false);

    if (!validateForm()) {
      return;
    }

    setLoading(true);

    try {
      await api.post('/contact', formData);
      setSuccess(true);
      setFormData({
        nome: '',
        email: '',
        empresa: '',
        telefone: '',
        assunto: '',
        mensagem: ''
      });
    } catch (err) {
      setError(
        err.response?.data?.message || 
        'Erro ao enviar mensagem. Tente novamente ou entre em contato por telefone.'
      );
    } finally {
      setLoading(false);
    }
  };

  const contactInfo = [
    {
      icon: <Mail size={20} />,
      label: 'E-mail',
      text: 'contato@docgest.com.br\nsuporte@docgest.com.br'
    },
    {
      icon: <Phone size={20} />,
      label: 'Telefone',
      text: '(11) 3000-0000\n(11) 99999-9999'
    },
    {
      icon: <MapPin size={20} />,
      label: 'Endereço',
      text: 'Av. Paulista, 1000\nSão Paulo - SP, 01310-100'
    },
    {
      icon: <Clock size={20} />,
      label: 'Horário de Atendimento',
      text: 'Segunda a Sexta: 8h às 18h\nSábado: 8h às 12h'
    }
  ];

  return (
    <Container>
      <LandingHeader />
      
      <HeroSection>
        <HeroContent>
          <HeroTitle>
            Entre em Contato
          </HeroTitle>
          <HeroSubtitle>
            Estamos aqui para ajudar! Entre em contato conosco para tirar dúvidas, 
            solicitar uma demonstração ou falar sobre suas necessidades específicas.
          </HeroSubtitle>
        </HeroContent>
      </HeroSection>

      <ContactSection>
        <SectionContainer>
          <ContactGrid>
            <ContactInfo>
              <div>
                <InfoTitle>Fale Conosco</InfoTitle>
                <InfoDescription>
                  Nossa equipe está pronta para atendê-lo e encontrar a melhor solução 
                  para sua empresa. Escolha a forma de contato que preferir.
                </InfoDescription>
              </div>
              
              <InfoList>
                {contactInfo.map((info, index) => (
                  <InfoItem key={index}>
                    <InfoIcon>
                      {info.icon}
                    </InfoIcon>
                    <InfoContent>
                      <InfoLabel>{info.label}</InfoLabel>
                      <InfoText>
                        {info.text.split('\n').map((line, lineIndex) => (
                          <span key={lineIndex}>
                            {line}
                            {lineIndex < info.text.split('\n').length - 1 && <br />}
                          </span>
                        ))}
                      </InfoText>
                    </InfoContent>
                  </InfoItem>
                ))}
              </InfoList>
            </ContactInfo>

            <ContactForm>
              <FormTitle>Envie sua Mensagem</FormTitle>
              <FormDescription>
                Preencha o formulário abaixo e entraremos em contato em até 24 horas.
              </FormDescription>

              {error && <ErrorMessage>{error}</ErrorMessage>}
              {success && (
                <SuccessMessage>
                  <CheckCircle size={16} />
                  Mensagem enviada com sucesso! Entraremos em contato em breve.
                </SuccessMessage>
              )}

              <Form onSubmit={handleSubmit}>
                <FormRow>
                  <Input
                    label="Nome Completo *"
                    name="nome"
                    type="text"
                    value={formData.nome}
                    onChange={handleChange}
                    placeholder="Seu nome completo"
                    required
                  />
                  <Input
                    label="E-mail *"
                    name="email"
                    type="email"
                    value={formData.email}
                    onChange={handleChange}
                    placeholder="seu@email.com"
                    required
                  />
                </FormRow>

                <FormRow>
                  <Input
                    label="Empresa"
                    name="empresa"
                    type="text"
                    value={formData.empresa}
                    onChange={handleChange}
                    placeholder="Nome da sua empresa"
                  />
                  <Input
                    label="Telefone"
                    name="telefone"
                    type="tel"
                    value={formData.telefone}
                    onChange={handleChange}
                    placeholder="(11) 99999-9999"
                  />
                </FormRow>

                <Input
                  label="Assunto *"
                  name="assunto"
                  type="text"
                  value={formData.assunto}
                  onChange={handleChange}
                  placeholder="Qual o motivo do seu contato?"
                  required
                />

                <div>
                  <label style={{ 
                    display: 'block', 
                    marginBottom: '0.5rem', 
                    fontSize: '0.875rem', 
                    fontWeight: '500',
                    color: '#374151'
                  }}>
                    Mensagem *
                  </label>
                  <TextArea
                    name="mensagem"
                    value={formData.mensagem}
                    onChange={handleChange}
                    placeholder="Descreva sua necessidade ou dúvida em detalhes..."
                    required
                  />
                </div>

                <Button 
                  type="submit" 
                  variant="primary" 
                  size="lg" 
                  $fullWidth
                  disabled={loading}
                >
                  {loading ? (
                    'Enviando...'
                  ) : (
                    <>
                      <Send size={16} />
                      Enviar Mensagem
                    </>
                  )}
                </Button>
              </Form>
            </ContactForm>
          </ContactGrid>
        </SectionContainer>
      </ContactSection>

      <Footer>
        <FooterText>
          © 2024 DocGest. Todos os direitos reservados.
        </FooterText>
      </Footer>
    </Container>
  );
}

export default Contato;