import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';

// Create axios instance
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: false,
});

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle auth errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const originalRequest = error.config;
    const status = error.response?.status;

    // List of public routes that should not trigger a global redirect on 401/400
    const publicAuthPaths = ['/auth/login', '/auth/register']; // Keep this line as is

    // Only redirect if it's a 401 error and not from a public auth route
    if (status === 401 && !publicAuthPaths.some(path => originalRequest.url.endsWith(path))) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      console.log('API Interceptor: Redirecting to / due to 401 on non-public route.');
      window.location.href = '/'; // Redirect to login
    }
    return Promise.reject(error);
  }
);

// Auth API
export const authAPI = {
  register: (userData: {
    email: string;
    password: string;
    name: string;
    role?: string;
    referral_code?: string;
  }) => api.post('/auth/register', userData),
  
  login: (credentials: { email: string; password: string }) =>
    api.post('/auth/login', credentials),
  
  getProfile: () => api.get('/auth/profile'),
  
  verifyToken: () => api.get('/auth/verify'),
};

// Affiliate API
export const affiliateAPI = {
  getDashboard: () => api.get('/affiliate/dashboard'),
  
  generateReferralLink: (customCode?: string) =>
    api.post('/affiliate/links', { custom_code: customCode }),
  
  getReferralLinks: () => api.get('/affiliate/links'),
  
  sendEmailInvite: (email: string, name?: string, phoneNumber?: string) => {
    const payload: { email: string; name?: string; phone_number?: string } = { email };
    if (name && name.trim()) {
      payload.name = name.trim();
    }
    if (phoneNumber && phoneNumber.trim()) {
      payload.phone_number = phoneNumber.trim();
    }
    return api.post('/affiliate/email-invite', payload);
  },
  
  getEmailInvites: () => api.get('/affiliate/email-invites'),
  
  getReferralTree: () => api.get('/affiliate/referral-tree'),
  
  getCommissions: () => api.get('/affiliate/commissions'),
  
  recordLinkClick: (linkCode: string) =>
    api.post(`/affiliate/links/${linkCode}/click`),
};

// Transaction API
export const transactionAPI = {
  create: (transactionData: {
    customer_email: string;
    amount: number;
    referral_code?: string;
    transaction_type?: string;
  }) => api.post('/transaction', transactionData),
  
  recordPublic: (transactionData: {
    customer_email: string;
    amount: number;
    referral_code?: string;
    transaction_type?: string;
  }) => api.post('/transaction/record', transactionData),
  
  getByAffiliate: () => api.get('/transaction/affiliate'),
};

// Admin API
export const adminAPI = {
  getDashboard: () => api.get('/admin/dashboard'),
  getAnalytics: (timeRange?: string) => api.get('/admin/analytics', { params: { timeRange } }),
  getTopAffiliates: (limit?: number) => api.get('/admin/top-affiliates', { params: { limit } }),
  
  getAffiliates: (page?: number, limit?: number) =>
    api.get('/admin/affiliates', { params: { page, limit } }),
  
  getAffiliateDetails: (affiliateId: string) =>
    api.get(`/admin/affiliates/${affiliateId}`),
  
  getAffiliateBankDetails: (affiliateId: string) =>
    api.get(`/admin/affiliates/${affiliateId}/bank-details`),
  
  getAffiliateCommissions: (affiliateId: string) =>
    api.get(`/admin/affiliates/${affiliateId}/commissions`),
  
  processAffiliatePayment: (affiliateId: string, amount: number, bankDetailId: string) =>
    api.post(`/admin/affiliates/${affiliateId}/payments`, { amount, bank_detail_id: bankDetailId }),
  
  updateAffiliateStatus: (affiliateId: string, isActive: boolean) =>
    api.patch(`/admin/affiliates/${affiliateId}/status`, { isActive }),
  
  deleteAffiliate: (affiliateId: string) =>
    api.delete(`/admin/affiliates/${affiliateId}`),
  
  getAffiliateEmailReferrals: (affiliateId: string) =>
    api.get(`/admin/affiliates/${affiliateId}/email-referrals`),
  
  getAffiliateEmailStats: (affiliateId: string) =>
    api.get(`/admin/affiliates/${affiliateId}/email-stats`),
  
  // Export report
  exportReport: () => 
    api.get('/admin/export-report', { responseType: 'blob' }),
  
  // Coordinator Management
  getCoordinators: () => api.get('/admin/coordinators'),
  getCoordinatorNetwork: (coordinatorId: string) => api.get(`/admin/coordinators/${coordinatorId}/network`),
  updateCoordinatorStatus: (coordinatorId: string, isActive: boolean) =>
    api.patch(`/admin/coordinators/${coordinatorId}/status`, { isActive }),
  exportCoordinatorReport: () => api.get('/admin/coordinators/export-report', { responseType: 'blob' }),
  
  getTransactions: (page?: number, limit?: number) =>
    api.get('/admin/transactions', { params: { page, limit } }),
  
  getPendingCommissions: (page?: number, limit?: number) =>
    api.get('/admin/commissions/pending', { params: { page, limit } }),
  
  approveCommissions: (commissionIds: string[]) =>
    api.post('/admin/commissions/approve', { commission_ids: commissionIds }),
  
  payCommissions: (commissionIds: string[]) =>
    api.post('/admin/commissions/pay', { commission_ids: commissionIds }),
  
  updateCommissionStatus: (commissionId: string, status: string) =>
    api.patch(`/admin/commissions/${commissionId}/status`, { status }),
};

// Coordinator API
export const coordinatorAPI = {
  getDashboard: () => api.get('/coordinator/dashboard'),
  
  getAffiliates: (page?: number, limit?: number) =>
    api.get('/coordinator/affiliates', { params: { page, limit } }),
  
  getAffiliateDetails: (affiliateId: string) =>
    api.get(`/coordinator/affiliates/${affiliateId}`),
  
  updateAffiliateStatus: (affiliateId: string, isActive: boolean) =>
    api.patch(`/coordinator/affiliates/${affiliateId}/status`, { isActive }),
  
  getReferrals: (page?: number, limit?: number) =>
    api.get('/coordinator/referrals', { params: { page, limit } }),
  
  getPayments: (page?: number, limit?: number) =>
    api.get('/coordinator/payments', { params: { page, limit } }),
  
  getCommissions: (page?: number, limit?: number) =>
    api.get('/coordinator/commissions', { params: { page, limit } }),
  
  sendEmailReferral: (email: string, name?: string, message?: string) =>
    api.post('/coordinator/email-referrals', { email, name, message }),
  
  getEmailReferrals: (page?: number, limit?: number) =>
    api.get('/coordinator/email-referrals', { params: { page, limit } }),
  
  getReferralKey: () => api.get('/coordinator/referral-key'),
  
  assignAffiliate: (affiliateId: string) =>
    api.post(`/coordinator/affiliates/${affiliateId}/assign`),
  
  removeAffiliate: (affiliateId: string) =>
    api.delete(`/coordinator/affiliates/${affiliateId}/assign`),
  
  registerAffiliate: (affiliateData: {
    name: string;
    email: string;
    password: string;
  }) => api.post('/coordinator/affiliates/register', affiliateData),
};

export default api;