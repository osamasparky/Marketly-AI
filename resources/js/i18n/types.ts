export type SupportedLocale = 'en' | 'ar';

export interface TranslationSchema {
  common: {
    appName: string;
    tagline: string;
    loading: string;
    processing: string;
    refresh: string;
    save: string;
    cancel: string;
    logout: string;
    status: string;
    healthy: string;
    error: string;
    success: string;
    active: string;
    ready: string;
    pending: string;
    next: string;
    completed: string;
    version: string;
  };
  navigation: {
    workspace: string;
    dashboard: string;
    brandBrain: string;
    strategy: string;
    content: string;
    creative: string;
    calendar: string;
    publishing: string;
    analytics: string;
    assistant: string;
    settings: string;
  };
  tenancy: {
    selectOrg: string;
    createOrg: string;
    orgName: string;
    orgType: string;
    business: string;
    agency: string;
    membersTitle: string;
    inviteMember: string;
    inviteEmail: string;
    role: string;
    ownerRole: string;
    adminRole: string;
    managerRole: string;
    editorRole: string;
    viewerRole: string;
    sendInvite: string;
    switchSuccess: string;
  };
  dashboard: {
    welcome: string;
    welcomeDesc: string;
    phaseBadge: string;
    healthTitle: string;
    providerContracts: string;
    domainModules: string;
    roadmapTitle: string;
    authConsoleTitle: string;
    authConsoleDesc: string;
    registerMode: string;
    loginMode: string;
    forgotMode: string;
    fullName: string;
    email: string;
    password: string;
    btnRegister: string;
    btnLogin: string;
    btnForgot: string;
    toggleToLogin: string;
    toggleToRegister: string;
    toggleToForgot: string;
    responseStream: string;
    callMeBtn: string;
    activeToken: string;
    loggedOut: string;
  };
  validation: {
    required: string;
    invalidEmail: string;
    passwordTooShort: string;
  };
}
