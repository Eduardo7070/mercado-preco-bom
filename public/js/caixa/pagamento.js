"use strict";
import('./atendimento.js').then(module => module.iniciarAtendimento('pagamento')).catch(() => MPBAlert.error('Não foi possível carregar o atendimento. Atualize a página.'));
