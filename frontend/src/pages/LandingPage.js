import React from 'react';
import styled from 'styled-components';
import { useNavigate } from 'react-router-dom';
import Button from '../components/Button';
import LandingHeader from '../components/layout/LandingHeader';

import { CheckCircle, Shield, Clock, Users, FileText, Zap } from 'lucide-react';
import { useApp } from '../contexts/AppContext';

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
  font-size: 3.5rem;
  font-weight: 800;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 1.5rem;
  line-height: 1.1;
  
  @media (max-width: 768px) {
    font-size: 2.5rem;
  }
`;

const HeroSubtitle = styled.p`
  font-size: 1.25rem;
  color: ${props => props.theme.colors.gray[600]};
  margin-bottom: 2.5rem;
  max-width: 2xl;
  margin-left: auto;
  margin-right: auto;
  line-height: 1.6;
  
  @media (max-width: 768px) {
    font-size: 1.125rem;
  }
`;

const HeroButtons = styled.div`
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 3rem;
`;

const FeaturesSection = styled.section`
  padding: 4rem 1.5rem;
  background-color: ${props => props.theme.colors.white};
`;

const SectionContainer = styled.div`
  max-width: 6xl;
  margin: 0 auto;
`;

const SectionTitle = styled.h2`
  font-size: 2.5rem;
  font-weight: 700;
  color: ${props => props.theme.colors.gray[900]};
  text-align: center;
  margin-bottom: 1rem;
  
  @media (max-width: 768px) {
    font-size: 2rem;
  }
`;

const SectionSubtitle = styled.p`
  font-size: 1.125rem;
  color: ${props => props.theme.colors.gray[600]};
  text-align: center;
  margin-bottom: 3rem;
  max-width: 3xl;
  margin-left: auto;
  margin-right: auto;
`;

const FeaturesGrid = styled.div`
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-bottom: 4rem;
`;

const FeatureCard = styled.div`
  background-color: ${props => props.theme.colors.white};
  border: 1px solid ${props => props.theme.colors.gray[200]};
  border-radius: 0.75rem;
  padding: 2rem;
  text-align: center;
  transition: all 0.3s ease-in-out;
  
  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  }
`;

const FeatureIcon = styled.div`
  width: 4rem;
  height: 4rem;
  background-color: ${props => props.theme.colors.gray[100]};
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  color: ${props => props.theme.colors.gray[700]};
`;

const FeatureTitle = styled.h3`
  font-size: 1.25rem;
  font-weight: 600;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 1rem;
`;

const FeatureDescription = styled.p`
  color: ${props => props.theme.colors.gray[600]};
  line-height: 1.6;
`;

const BenefitsSection = styled.section`
  padding: 4rem 1.5rem;
  background-color: ${props => props.theme.colors.gray[50]};
`;

const BenefitsList = styled.div`
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  max-width: 4xl;
  margin: 0 auto;
`;

const BenefitItem = styled.div`
  display: flex;
  align-items: flex-start;
  gap: 1rem;
`;

const BenefitIcon = styled.div`
  color: ${props => props.theme.colors.success};
  margin-top: 0.25rem;
`;

const BenefitText = styled.div`
  flex: 1;
`;

const BenefitTitle = styled.h4`
  font-weight: 600;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 0.5rem;
`;

const BenefitDescription = styled.p`
  color: ${props => props.theme.colors.gray[600]};
  font-size: 0.875rem;
  line-height: 1.5;
`;

const CTASection = styled.section`
  padding: 4rem 1.5rem;
  background-color: ${props => props.theme.colors.gray[900]};
  text-align: center;
`;

const CTATitle = styled.h2`
  font-size: 2.5rem;
  font-weight: 700;
  color: ${props => props.theme.colors.white};
  margin-bottom: 1rem;
  
  @media (max-width: 768px) {
    font-size: 2rem;
  }
`;

const CTASubtitle = styled.p`
  font-size: 1.125rem;
  color: ${props => props.theme.colors.gray[300]};
  margin-bottom: 2.5rem;
  max-width: 2xl;
  margin-left: auto;
  margin-right: auto;
`;

const Footer = styled.footer`
  padding: 2rem 1.5rem;
  background-color: ${props => props.theme.colors.gray[900]};
  border-top: 1px solid ${props => props.theme.colors.gray[800]};
  text-align: center;
`;

const FooterText = styled.p`
  color: ${props => props.theme.colors.gray[400]};
  font-size: 0.875rem;
`;

function LandingPage() {
  const navigate = useNavigate();
  const { appInfo } = useApp();

  const handleTestClick = () => {
    navigate('/cadastro?tipo=empresa&teste=true');
  };

  const handleSignupClick = () => {
    navigate('/cadastro');
  };

  const handleLoginClick = () => {
    navigate('/login');
  };

  const features = [
    {
      icon: <FileText size={24} />,
      title: 'Gestão Completa de Documentos',
      description: 'Organize, armazene e gerencie todos os seus documentos em um só lugar, com controle total de versões e histórico.'
    },
    {
      icon: <Shield size={24} />,
      title: 'Assinatura Digital Segura',
      description: 'Assinatura eletrônica com validade jurídica, criptografia avançada e certificação digital para máxima segurança.'
    },
    {
      icon: <Clock size={24} />,
      title: 'Economia de Tempo',
      description: 'Reduza drasticamente o tempo gasto com processos burocráticos e agilize a aprovação de documentos.'
    },
    {
      icon: <Users size={24} />,
      title: 'Colaboração em Equipe',
      description: 'Permita que múltiplos usuários colaborem em documentos, com controle de permissões e fluxos de aprovação.'
    },
    {
      icon: <Zap size={24} />,
      title: 'Automação Inteligente',
      description: 'Automatize fluxos de trabalho, notificações e lembretes para nunca perder prazos importantes.'
    },
    {
      icon: <CheckCircle size={24} />,
      title: 'Conformidade Legal',
      description: 'Mantenha-se em conformidade com as regulamentações legais e tenha auditoria completa de todos os processos.'
    }
  ];

  const benefits = [
    {
      title: 'Redução de Custos',
      description: 'Elimine gastos com papel, impressão, correio e armazenamento físico de documentos.'
    },
    {
      title: 'Maior Produtividade',
      description: 'Acelere processos que antes levavam dias ou semanas para apenas alguns minutos.'
    },
    {
      title: 'Sustentabilidade',
      description: 'Contribua para o meio ambiente reduzindo drasticamente o uso de papel.'
    },
    {
      title: 'Acesso Remoto',
      description: 'Acesse seus documentos de qualquer lugar, a qualquer hora, em qualquer dispositivo.'
    },
    {
      title: 'Backup Automático',
      description: 'Seus documentos ficam seguros na nuvem com backup automático e redundância.'
    },
    {
      title: 'Integração Fácil',
      description: 'Integre facilmente com seus sistemas existentes através de APIs robustas.'
    }
  ];

  return (
    <Container>
      <LandingHeader />
      
      <HeroSection>
        <HeroContent>
          <HeroTitle>
            Transforme sua Gestão de Documentos
          </HeroTitle>
          <HeroSubtitle>
            A plataforma completa para digitalizar, organizar e assinar documentos com segurança jurídica. 
            Simplifique seus processos e aumente a produtividade da sua empresa.
          </HeroSubtitle>
          <HeroButtons>
            <Button 
              $variant="primary" 
              size="lg"
              onClick={handleTestClick}
            >
              Começar Teste Gratuito
            </Button>
            <Button 
              $variant="outline" 
              size="lg"
              onClick={() => navigate('/planos-public')}
            >
              Ver Planos
            </Button>
          </HeroButtons>
        </HeroContent>
      </HeroSection>

      <FeaturesSection>
        <SectionContainer>
          <SectionTitle>Por que escolher o {appInfo?.app_name || ''}?</SectionTitle>
          <SectionSubtitle>
            Nossa plataforma oferece todas as ferramentas necessárias para modernizar 
            a gestão de documentos da sua empresa
          </SectionSubtitle>
          
          <FeaturesGrid>
            {features.map((feature, index) => (
              <FeatureCard key={index}>
                <FeatureIcon>
                  {feature.icon}
                </FeatureIcon>
                <FeatureTitle>{feature.title}</FeatureTitle>
                <FeatureDescription>{feature.description}</FeatureDescription>
              </FeatureCard>
            ))}
          </FeaturesGrid>
        </SectionContainer>
      </FeaturesSection>

      <BenefitsSection>
        <SectionContainer>
          <SectionTitle>Benefícios Imediatos</SectionTitle>
          <SectionSubtitle>
            Veja como nossa plataforma pode transformar sua empresa desde o primeiro dia
          </SectionSubtitle>
          
          <BenefitsList>
            {benefits.map((benefit, index) => (
              <BenefitItem key={index}>
                <BenefitIcon>
                  <CheckCircle size={20} />
                </BenefitIcon>
                <BenefitText>
                  <BenefitTitle>{benefit.title}</BenefitTitle>
                  <BenefitDescription>{benefit.description}</BenefitDescription>
                </BenefitText>
              </BenefitItem>
            ))}
          </BenefitsList>
        </SectionContainer>
      </BenefitsSection>

      <CTASection>
        <SectionContainer>
          <CTATitle>Pronto para começar?</CTATitle>
          <CTASubtitle>
            Junte-se a milhares de empresas que já transformaram sua gestão de documentos. 
            Comece seu teste gratuito hoje mesmo!
          </CTASubtitle>
          <HeroButtons>
            <Button 
              $variant="white" 
              size="lg"
              onClick={handleTestClick}
            >
              Iniciar Teste Gratuito
            </Button>
            <Button 
              $variant="outline-white" 
              size="lg"
              onClick={handleLoginClick}
            >
              Já tenho conta
            </Button>
          </HeroButtons>
        </SectionContainer>
      </CTASection>

      <Footer>
        <FooterText>
          © 2024 {appInfo.app_name}. Todos os direitos reservados.
        </FooterText>
      </Footer>
    </Container>
  );
}

export default LandingPage;