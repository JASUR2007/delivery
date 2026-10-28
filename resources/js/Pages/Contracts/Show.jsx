import React, { useState } from 'react';
import { usePage, useForm, router, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { 
    Calendar, 
    Package, 
    ArrowLeft, 
    CheckCircle, 
    Clock, 
    AlertTriangle, 
    DollarSign, 
    ShieldCheck, 
    ShieldAlert, 
    FileText, 
    Plus, 
    RotateCcw, 
    Check, 
    X,
    Info,
    Ban
} from 'lucide-react';

export default function ContractShow({ contract, penalty_preview, budget_items = [], query_as_of_date }) {
    const { auth, flash = {} } = usePage().props;
    const currentUser = auth?.user;
    const isExecutor = currentUser?.role === 'executor';
    const isSupervisor = currentUser?.role === 'supervisor';
    const isAccountant = currentUser?.role === 'accountant';

    const [activeTab, setActiveTab] = useState('items'); // 'items', 'penalty', 'accounting'
    const [asOfDate, setAsOfDate] = useState(query_as_of_date || new Date().toISOString().slice(0, 10));

    // Batch Modal State
    const [isBatchModalOpen, setIsBatchModalOpen] = useState(false);
    const { data: batchData, setData: setBatchData, post: postBatch, processing: batchProcessing, errors: batchErrors, reset: resetBatch } = useForm({
        delivery_date: new Date().toISOString().slice(0, 10),
        notes: '',
        items: contract.items.map(item => ({
            contract_item_id: item.id,
            delivered_paid_quantity: 0,
            delivered_bonus_quantity: 0
        }))
    });

    // Payment Form State
    const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);
    const { data: paymentData, setData: setPaymentData, post: postPayment, processing: paymentProcessing, errors: paymentErrors, reset: resetPayment } = useForm({
        contract_id: contract.id,
        milestone_id: contract.payment_milestones[0]?.id || '',
        budget_item_id: budget_items[0]?.id || '',
        amount: '',
        payment_date: new Date().toISOString().slice(0, 10),
        payment_number: `TO-${Date.now().toString().slice(-6)}`,
        comment: ''
    });

    // Void Modal State
    const [voidPaymentId, setVoidPaymentId] = useState(null);
    const { data: voidData, setData: setVoidData, post: postVoid, processing: voidProcessing, errors: voidErrors, reset: resetVoid } = useForm({
        void_reason: ''
    });

    // Supervisor Review State
    const [reviewPenaltyId, setReviewPenaltyId] = useState(null);
    const { data: reviewData, setData: setReviewData, post: postReview, processing: reviewProcessing, reset: resetReview } = useForm({
        status: 'approved',
        approved_amount: '',
        notes: ''
    });

    const handleAsOfDateChange = (newDate) => {
        setAsOfDate(newDate);
        router.get(`/contracts/${contract.id}`, { calculation_date: newDate }, { preserveState: true, preserveScroll: true });
    };

    const handleActivate = () => {
        if (confirm('Shartnomani faollashtirmoqchimisiz? Faollashtirilgandan so‘ng tovar qatorlarini o‘zgartirib bo‘lmaydi.')) {
            router.post(`/contracts/${contract.id}/activate`);
        }
    };

    const handleBatchSubmit = (e) => {
        e.preventDefault();
        postBatch(`/contracts/${contract.id}/batches`, {
            onSuccess: () => {
                setIsBatchModalOpen(false);
                resetBatch();
            }
        });
    };

    const handleSavePenalty = () => {
        router.post(`/contracts/${contract.id}/penalties`, { calculation_date: asOfDate });
    };

    const handleOpenReview = (penalty) => {
        setReviewPenaltyId(penalty.id);
        setReviewData({
            status: 'approved',
            approved_amount: penalty.calculated_amount,
            notes: ''
        });
    };

    const handleReviewSubmit = (e) => {
        e.preventDefault();
        postReview(`/penalties/${reviewPenaltyId}/review`, {
            onSuccess: () => {
                setReviewPenaltyId(null);
                resetReview();
            }
        });
    };

    const handlePaymentSubmit = (e) => {
        e.preventDefault();
        postPayment('/payments', {
            onSuccess: () => {
                setIsPaymentModalOpen(false);
                resetPayment();
            }
        });
    };

    const handleVoidSubmit = (e) => {
        e.preventDefault();
        postVoid(`/payments/${voidPaymentId}/void`, {
            onSuccess: () => {
                setVoidPaymentId(null);
                resetVoid();
            }
        });
    };

    const getStatusBadge = (status, label) => {
        switch (status) {
            case 'draft':
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800 border border-slate-200">{label}</span>;
            case 'active':
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">{label}</span>;
            case 'completed':
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">{label}</span>;
            default:
                return null;
        }
    };

    const getMilestoneStatusBadge = (status, label) => {
        switch (status) {
            case 'planned':
                return <span className="px-2.5 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-700">{label}</span>;
            case 'ready_to_pay':
                return <span className="px-2.5 py-0.5 rounded-md text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">{label}</span>;
            case 'partially_paid':
                return <span className="px-2.5 py-0.5 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">{label}</span>;
            case 'paid':
                return <span className="px-2.5 py-0.5 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">{label}</span>;
            case 'overdue':
                return <span className="px-2.5 py-0.5 rounded-md text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">{label}</span>;
            default:
                return null;
        }
    };

    return (
        <AppLayout>
            <div className="space-y-6">
                {/* Back button and breadcrumb */}
                <div className="flex items-center justify-between">
                    <Link
                        href="/"
                        className="inline-flex items-center text-xs font-semibold text-slate-600 hover:text-slate-900 transition"
                    >
                        <ArrowLeft className="w-3.5 h-3.5 mr-1" />
                        Shartnomalar ro‘yxatiga qaytish
                    </Link>

                    {contract.status === 'draft' && isExecutor && (
                        <button
                            onClick={handleActivate}
                            className="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                        >
                            <Check className="w-4 h-4 mr-1.5" />
                            Shartnomani faollashtirish
                        </button>
                    )}
                </div>

                {/* Summary Card */}
                <div className="bg-white border border-slate-200 rounded-xl p-6 shadow-xs">
                    <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-6 border-b border-slate-100">
                        <div>
                            <div className="flex items-center space-x-3">
                                <h2 className="text-xl font-bold text-slate-900">
                                    Shartnoma: {contract.contract_number}
                                </h2>
                                {getStatusBadge(contract.status, contract.status_label)}
                            </div>
                            <p className="text-sm text-slate-600 mt-1">
                                Yetkazib beruvchi: <span className="font-semibold text-slate-800">{contract.supplier_name}</span>
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-4 text-xs">
                            <div className="bg-slate-50 border border-slate-200 p-2.5 rounded-lg">
                                <div className="text-slate-500">Boshlanish sanasi</div>
                                <div className="font-bold text-slate-900 mt-0.5">{contract.start_date}</div>
                            </div>
                            <div className="bg-slate-50 border border-slate-200 p-2.5 rounded-lg">
                                <div className="text-slate-500">Muddat sanasi</div>
                                <div className="font-bold text-slate-900 mt-0.5">{contract.deadline_date} ({contract.duration_days} kun)</div>
                            </div>
                            <div className="bg-indigo-50 border border-indigo-100 p-2.5 rounded-lg">
                                <div className="text-indigo-600 font-medium">Jami pullik summa</div>
                                <div className="font-bold text-indigo-900 mt-0.5">{contract.total_paid_amount} so‘m</div>
                            </div>
                        </div>
                    </div>

                    {/* Progress indicators */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 text-xs">
                        <div className="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                            <span className="text-slate-600">Pullik tovar kelishi:</span>
                            <span className="font-bold text-slate-900">
                                {contract.delivered_paid_quantity} / {contract.total_paid_quantity} dona
                                {contract.remaining_paid_quantity > 0 ? (
                                    <span className="text-amber-600 ml-1">({contract.remaining_paid_quantity} qoldi)</span>
                                ) : (
                                    <span className="text-emerald-600 ml-1">(To‘liq kelgan)</span>
                                )}
                            </span>
                        </div>
                        <div className="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                            <span className="text-slate-600">Bonus tovar kelishi:</span>
                            <span className="font-bold text-slate-900">
                                {contract.delivered_bonus_quantity} / {contract.total_bonus_quantity} dona
                            </span>
                        </div>
                        <div className="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                            <span className="text-slate-600">Tasdiqlangan jarima:</span>
                            <span className="font-bold text-rose-600">
                                {contract.latest_approved_penalty_amount} so‘m
                            </span>
                        </div>
                    </div>
                </div>

                {/* Tabs */}
                <div className="border-b border-slate-200">
                    <nav className="flex space-x-6 text-sm font-medium">
                        <button
                            onClick={() => setActiveTab('items')}
                            className={`pb-3 border-b-2 font-semibold transition ${
                                activeTab === 'items'
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-700'
                            }`}
                        >
                            1. Tovar qatorlari va Partiyalar
                        </button>
                        <button
                            onClick={() => setActiveTab('penalty')}
                            className={`pb-3 border-b-2 font-semibold transition ${
                                activeTab === 'penalty'
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-700'
                            }`}
                        >
                            2. Kechikish va Jarima hisobi
                        </button>
                        <button
                            onClick={() => setActiveTab('accounting')}
                            className={`pb-3 border-b-2 font-semibold transition ${
                                activeTab === 'accounting'
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-700'
                            }`}
                        >
                            3. Buxgalteriya va To‘lovlar
                        </button>
                    </nav>
                </div>

                {/* Tab 1: Items & Deliveries */}
                {activeTab === 'items' && (
                    <div className="space-y-6">
                        {/* Items Balance Table */}
                        <div className="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
                            <div className="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                                <div>
                                    <h3 className="text-sm font-bold text-slate-900">Tovarlar va Qoldiqlar jadvali</h3>
                                    <p className="text-xs text-slate-500">Pullik va bepul (bonus) tovarlar alohida yuritiladi</p>
                                </div>
                                {isExecutor && contract.status === 'active' && (
                                    <button
                                        onClick={() => setIsBatchModalOpen(true)}
                                        className="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                                    >
                                        <Plus className="w-3.5 h-3.5 mr-1" />
                                        Kelgan partiyani kiritish
                                    </button>
                                )}
                            </div>

                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-slate-200 text-xs">
                                    <thead className="bg-slate-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600 uppercase">Tovar nomi</th>
                                            <th className="px-4 py-3 text-right font-semibold text-slate-600 uppercase">Narxi</th>
                                            <th className="px-4 py-3 text-center font-semibold text-slate-600 uppercase bg-blue-50/50">Pullik reja</th>
                                            <th className="px-4 py-3 text-center font-semibold text-slate-600 uppercase bg-blue-50/50">Pullik kelgan</th>
                                            <th className="px-4 py-3 text-center font-semibold text-slate-600 uppercase bg-blue-50/50">Pullik qoldiq</th>
                                            <th className="px-4 py-3 text-center font-semibold text-slate-600 uppercase bg-emerald-50/50">Bonus reja</th>
                                            <th className="px-4 py-3 text-center font-semibold text-slate-600 uppercase bg-emerald-50/50">Bonus kelgan</th>
                                            <th className="px-4 py-3 text-center font-semibold text-slate-600 uppercase bg-emerald-50/50">Bonus qoldiq</th>
                                            <th className="px-4 py-3 text-right font-semibold text-slate-600 uppercase">Jami summa</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-200">
                                        {contract.items.map((item) => (
                                            <tr key={item.id} className="hover:bg-slate-50">
                                                <td className="px-4 py-3 font-medium text-slate-900">{item.item_name}</td>
                                                <td className="px-4 py-3 text-right text-slate-700">{item.unit_price} so‘m</td>
                                                <td className="px-4 py-3 text-center font-semibold text-slate-900 bg-blue-50/20">{item.paid_quantity}</td>
                                                <td className="px-4 py-3 text-center font-semibold text-blue-700 bg-blue-50/20">{item.delivered_paid_quantity}</td>
                                                <td className="px-4 py-3 text-center font-bold text-amber-600 bg-blue-50/20">{item.remaining_paid_quantity}</td>
                                                <td className="px-4 py-3 text-center text-slate-700 bg-emerald-50/20">{item.bonus_quantity}</td>
                                                <td className="px-4 py-3 text-center text-emerald-700 bg-emerald-50/20">{item.delivered_bonus_quantity}</td>
                                                <td className="px-4 py-3 text-center text-slate-700 bg-emerald-50/20">{item.remaining_bonus_quantity}</td>
                                                <td className="px-4 py-3 text-right font-bold text-slate-900">{item.total_paid_amount} so‘m</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {/* Delivery Batches History */}
                        <div className="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
                            <div className="p-4 bg-slate-50 border-b border-slate-200">
                                <h3 className="text-sm font-bold text-slate-900">Qabul qilingan partiyalar tarixi</h3>
                            </div>
                            {contract.delivery_batches.length === 0 ? (
                                <div className="p-8 text-center text-xs text-slate-500">
                                    Hozircha hech qanday partiya qabul qilinmagan.
                                </div>
                            ) : (
                                <div className="divide-y divide-slate-200">
                                    {contract.delivery_batches.map((batch) => (
                                        <div key={batch.id} className="p-4 text-xs space-y-2">
                                            <div className="flex justify-between items-center">
                                                <div className="font-semibold text-slate-900">
                                                    Partiya sanasi: <span className="text-indigo-600">{batch.delivery_date}</span>
                                                    {batch.notes && <span className="text-slate-500 font-normal ml-2">({batch.notes})</span>}
                                                </div>
                                                <span className="text-slate-400">Kiritdi: {batch.creator?.name}</span>
                                            </div>
                                            <div className="flex flex-wrap gap-2 pt-1">
                                                {batch.items.map((bItem) => (
                                                    <span key={bItem.id} className="px-2.5 py-1 bg-slate-100 rounded-md text-slate-700">
                                                        {bItem.item_name}: <strong className="text-blue-700">{bItem.delivered_paid_quantity} pullik</strong>, <strong className="text-emerald-700">{bItem.delivered_bonus_quantity} bonus</strong>
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Tab 2: Penalty Calculation */}
                {activeTab === 'penalty' && (
                    <div className="space-y-6">
                        {/* Calculation Date Header */}
                        <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div className="flex items-center space-x-3">
                                <label className="text-xs font-bold text-slate-700 uppercase">
                                    Hisoblash sanasi:
                                </label>
                                <input
                                    type="date"
                                    value={asOfDate}
                                    onChange={(e) => handleAsOfDateChange(e.target.value)}
                                    className="px-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                />
                                <span className="text-xs text-slate-500">(Standart: bugun)</span>
                            </div>

                            <button
                                onClick={handleSavePenalty}
                                className="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                            >
                                <Clock className="w-3.5 h-3.5 mr-1.5" />
                                Jarimani hisoblab, boshliqqa yuborish
                            </button>
                        </div>

                        {/* Penalty Metric Cards */}
                        <div className="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
                                <span className="text-xs text-slate-500">Muddat sanasi</span>
                                <div className="text-base font-bold text-slate-900 mt-1">{penalty_preview.deadline_date}</div>
                                <span className="text-[10px] text-slate-400">Shartnoma bo‘yicha</span>
                            </div>

                            <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
                                <span className="text-xs text-slate-500">Kechikish kunlari</span>
                                <div className="text-base font-bold text-rose-600 mt-1">{penalty_preview.delay_days} kun</div>
                                <span className="text-[10px] text-slate-400">Muddatdan keyingi kunlar</span>
                            </div>

                            <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
                                <span className="text-xs text-slate-500">Hisoblangan jarima</span>
                                <div className="text-base font-bold text-rose-700 mt-1">{penalty_preview.calculated_amount} so‘m</div>
                                <span className="text-[10px] text-slate-400">Kuniga 0.5% stavkada</span>
                            </div>

                            <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
                                <span className="text-xs text-slate-500">Maksimal limit (50%)</span>
                                <div className="text-base font-bold text-slate-900 mt-1">{penalty_preview.cap_amount} so‘m</div>
                                <span className="text-[10px] text-slate-400">Kelmagan summaning 50% idan oshmaydi</span>
                            </div>
                        </div>

                        {/* Supervisor Review Area */}
                        {isSupervisor && contract.latest_penalty && contract.latest_penalty.status === 'pending' && (
                            <div className="bg-amber-50 border border-amber-200 rounded-xl p-5 shadow-xs">
                                <div className="flex items-center space-x-2 text-amber-900 font-bold text-sm mb-2">
                                    <ShieldAlert className="w-5 h-5 text-amber-600" />
                                    <span>Boshliq diqqatiga: Jarimani tasdiqlash yoki rad etish</span>
                                </div>
                                <p className="text-xs text-amber-800 mb-4">
                                    Hisoblangan summa: <strong>{contract.latest_penalty.calculated_amount} so‘m</strong> ({contract.latest_penalty.delay_days} kun kechikish).
                                    Siz summani o‘zgartirib tasdiqlashingiz yoki rad etishingiz mumkin.
                                </p>

                                <form onSubmit={handleReviewSubmit} className="space-y-4">
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                                        <div>
                                            <label className="block font-semibold text-slate-700 mb-1">Qaror</label>
                                            <select
                                                value={reviewData.status}
                                                onChange={(e) => setReviewData('status', e.target.value)}
                                                className="w-full px-3 py-1.5 border border-slate-300 rounded-lg bg-white"
                                            >
                                                <option value="approved">Tasdiqlash (Approved)</option>
                                                <option value="rejected">Rad etish (Rejected)</option>
                                            </select>
                                        </div>

                                        {reviewData.status === 'approved' && (
                                            <div>
                                                <label className="block font-semibold text-slate-700 mb-1">
                                                    Tasdiqlangan summa (ixtiyoriy o‘zgartirish)
                                                </label>
                                                <input
                                                    type="text"
                                                    value={reviewData.approved_amount}
                                                    onChange={(e) => setReviewData('approved_amount', e.target.value)}
                                                    placeholder="Bo‘sh qolsa hisoblangan summa olinadi"
                                                    className="w-full px-3 py-1.5 border border-slate-300 rounded-lg bg-white"
                                                />
                                            </div>
                                        )}

                                        <div>
                                            <label className="block font-semibold text-slate-700 mb-1">Izoh</label>
                                            <input
                                                type="text"
                                                value={reviewData.notes}
                                                onChange={(e) => setReviewData('notes', e.target.value)}
                                                placeholder="Boshliq izohi"
                                                className="w-full px-3 py-1.5 border border-slate-300 rounded-lg bg-white"
                                            />
                                        </div>
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={reviewProcessing}
                                        onClick={() => setReviewPenaltyId(contract.latest_penalty.id)}
                                        className="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs rounded-lg shadow-xs transition"
                                    >
                                        Qarorni saqlash
                                    </button>
                                </form>
                            </div>
                        )}

                        {/* Day-by-Day Penalty Table */}
                        <div className="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
                            <div className="p-4 bg-slate-50 border-b border-slate-200">
                                <h3 className="text-sm font-bold text-slate-900">Kun-kunlik jarima jadvali</h3>
                                <p className="text-xs text-slate-500">
                                    Qoida: Partiya sanasi X bo‘lsa, u X dan keyingi kundan hisobga olinadi. Har kunlik jarima = kelmagan summa × 0.5% (2 xonagacha yaxlitlangan).
                                </p>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-slate-200 text-xs">
                                    <thead className="bg-slate-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left font-semibold text-slate-600 uppercase">Sana</th>
                                            <th className="px-6 py-3 text-right font-semibold text-slate-600 uppercase">Hali kelmagan pullik summa</th>
                                            <th className="px-6 py-3 text-right font-semibold text-slate-600 uppercase">Shu kunlik jarima (0.5%)</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-200">
                                        {penalty_preview.breakdown.length === 0 ? (
                                            <tr>
                                                <td colSpan="3" className="px-6 py-8 text-center text-slate-500">
                                                    Belgilangan sana bo‘yicha jarima mavjud emas (kechikish yo‘q).
                                                </td>
                                            </tr>
                                        ) : (
                                            penalty_preview.breakdown.map((row, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50">
                                                    <td className="px-6 py-3 font-medium text-slate-900">{row.date}</td>
                                                    <td className="px-6 py-3 text-right text-slate-700">{row.undelivered_amount} so‘m</td>
                                                    <td className="px-6 py-3 text-right font-bold text-rose-600">{row.daily_penalty} so‘m</td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                )}

                {/* Tab 3: Accounting & Payments */}
                {activeTab === 'accounting' && (
                    <div className="space-y-6">
                        {/* Approved Penalty Notice */}
                        <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 shadow-xs flex justify-between items-center">
                            <div>
                                <span className="text-xs font-semibold text-slate-500 uppercase">Buxgalteriya uchun tasdiqlangan jarima:</span>
                                <div className="text-lg font-bold text-slate-900 mt-0.5">
                                    {contract.latest_approved_penalty_amount} so‘m
                                </div>
                                <span className="text-[11px] text-slate-500">
                                    Faqat boshliq tasdiqlagan jarima hisobga olinadi. Tasdiqlanmagan yoki rad etilgan jarima 0.00 ko‘rinadi.
                                </span>
                            </div>

                            {isAccountant && (
                                <button
                                    onClick={() => setIsPaymentModalOpen(true)}
                                    className="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                                >
                                    <Plus className="w-3.5 h-3.5 mr-1.5" />
                                    Yangi to‘lov kiritish
                                </button>
                            )}
                        </div>

                        {/* Payment Milestones (Bosqichlar) */}
                        <div className="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
                            <div className="p-4 bg-slate-50 border-b border-slate-200">
                                <h3 className="text-sm font-bold text-slate-900">To‘lov jadvali (Bosqichlar)</h3>
                                <p className="text-xs text-slate-500">
                                    Bosqich holati reestr asosida doimiy qayta hisoblanadi (qo‘lda o‘zgartirilmaydi)
                                </p>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-slate-200 text-xs">
                                    <thead className="bg-slate-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600 uppercase">Bosqich</th>
                                            <th className="px-4 py-3 text-center font-semibold text-slate-600 uppercase">Foiz</th>
                                            <th className="px-4 py-3 text-right font-semibold text-slate-600 uppercase">Reja summa</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600 uppercase">To‘lash sharti</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600 uppercase">Muddat sanasi</th>
                                            <th className="px-4 py-3 text-right font-semibold text-slate-600 uppercase">To‘langan</th>
                                            <th className="px-4 py-3 text-right font-semibold text-slate-600 uppercase">Qoldiq</th>
                                            <th className="px-4 py-3 text-center font-semibold text-slate-600 uppercase">Holat</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-200">
                                        {contract.payment_milestones.map((m) => (
                                            <tr key={m.id} className="hover:bg-slate-50">
                                                <td className="px-4 py-3 font-bold text-slate-900">{m.name}</td>
                                                <td className="px-4 py-3 text-center font-semibold text-slate-600">{m.percentage}%</td>
                                                <td className="px-4 py-3 text-right font-semibold text-slate-900">{m.amount} so‘m</td>
                                                <td className="px-4 py-3 text-slate-600">
                                                    <div className="flex items-center space-x-1.5">
                                                        <span className={`w-2 h-2 rounded-full ${m.is_condition_met ? 'bg-emerald-500' : 'bg-amber-500'}`} />
                                                        <span>{m.condition_label}</span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-slate-600">{m.due_date || '—'}</td>
                                                <td className="px-4 py-3 text-right font-semibold text-emerald-700">{m.paid_amount} so‘m</td>
                                                <td className="px-4 py-3 text-right font-bold text-slate-900">{m.remaining_balance} so‘m</td>
                                                <td className="px-4 py-3 text-center">
                                                    {getMilestoneStatusBadge(m.status, m.status_label)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {/* Payment Registry */}
                        <div className="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
                            <div className="p-4 bg-slate-50 border-b border-slate-200">
                                <h3 className="text-sm font-bold text-slate-900">To‘lovlar reestri</h3>
                                <p className="text-xs text-slate-500">
                                    To‘lovlar o‘chirilmaydi, xato bo‘lsa sabab bilan bekor qilinadi (voided).
                                </p>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-slate-200 text-xs">
                                    <thead className="bg-slate-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600 uppercase">To‘lov raqami</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600 uppercase">Sana</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600 uppercase">Bosqich</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600 uppercase">Smeta oyi</th>
                                            <th className="px-4 py-3 text-right font-semibold text-slate-600 uppercase">Summa</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600 uppercase">Kim kiritgan</th>
                                            <th className="px-4 py-3 text-center font-semibold text-slate-600 uppercase">Holat</th>
                                            <th className="px-4 py-3 text-right font-semibold text-slate-600 uppercase">Amallar</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-200">
                                        {contract.payment_milestones.flatMap(m => m.payments || []).length === 0 ? (
                                            <tr>
                                                <td colSpan="8" className="px-4 py-8 text-center text-slate-500">
                                                    Hozircha hech qanday to‘lov kiritilmagan.
                                                </td>
                                            </tr>
                                        ) : (
                                            contract.payment_milestones.flatMap(m => m.payments || []).map((p) => {
                                                const isVoid = p.status === 'voided';
                                                return (
                                                    <tr key={p.id} className={isVoid ? 'bg-slate-50 opacity-60' : 'hover:bg-slate-50'}>
                                                        <td className="px-4 py-3 font-semibold text-slate-900">{p.payment_number}</td>
                                                        <td className="px-4 py-3 text-slate-600">{p.payment_date}</td>
                                                        <td className="px-4 py-3 text-slate-800">{p.milestone_name}</td>
                                                        <td className="px-4 py-3 text-slate-600">{p.budget_month_name}</td>
                                                        <td className="px-4 py-3 text-right font-bold text-slate-900">
                                                            <span className={isVoid ? 'line-through text-slate-400' : 'text-emerald-700'}>
                                                                {p.amount} so‘m
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-3 text-slate-600">{p.creator?.name}</td>
                                                        <td className="px-4 py-3 text-center">
                                                            {isVoid ? (
                                                                <div className="flex flex-col items-center">
                                                                    <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-800">
                                                                        Bekor qilingan
                                                                    </span>
                                                                    <span className="text-[10px] text-slate-500 mt-0.5">Sabab: {p.void_reason}</span>
                                                                </div>
                                                            ) : (
                                                                <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                                                    Faol
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-3 text-right">
                                                            {isAccountant && !isVoid && (
                                                                <button
                                                                    onClick={() => setVoidPaymentId(p.id)}
                                                                    className="text-rose-600 hover:text-rose-900 font-semibold"
                                                                >
                                                                    Bekor qilish
                                                                </button>
                                                            )}
                                                        </td>
                                                    </tr>
                                                );
                                            })
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                )}

                {/* Modal: Record Delivery Batch */}
                {isBatchModalOpen && (
                    <div className="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
                        <div className="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 space-y-4">
                            <div className="flex justify-between items-center border-b border-slate-100 pb-3">
                                <h3 className="text-base font-bold text-slate-900">Kelgan partiyani kiritish</h3>
                                <button onClick={() => setIsBatchModalOpen(false)} className="text-slate-400 hover:text-slate-600">
                                    <X className="w-5 h-5" />
                                </button>
                            </div>

                            <form onSubmit={handleBatchSubmit} className="space-y-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Yetkazilgan sana</label>
                                    <input
                                        type="date"
                                        value={batchData.delivery_date}
                                        onChange={(e) => setBatchData('delivery_date', e.target.value)}
                                        className="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                                        required
                                    />
                                </div>

                                <div className="space-y-3">
                                    <label className="block text-xs font-semibold text-slate-700">Tovarlar bo‘yicha miqdorlar</label>
                                    {contract.items.map((item, idx) => (
                                        <div key={item.id} className="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-2">
                                            <div className="text-xs font-bold text-slate-800">{item.item_name}</div>
                                            <div className="text-[11px] text-slate-500">
                                                Pullik qoldiq: <strong>{item.remaining_paid_quantity}</strong> | Bonus qoldiq: <strong>{item.remaining_bonus_quantity}</strong>
                                            </div>
                                            <div className="grid grid-cols-2 gap-2 pt-1">
                                                <div>
                                                    <label className="block text-[10px] text-slate-600">Yetkazilgan pullik</label>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max={item.remaining_paid_quantity}
                                                        value={batchData.items[idx]?.delivered_paid_quantity || 0}
                                                        onChange={(e) => {
                                                            const newItems = [...batchData.items];
                                                            newItems[idx].delivered_paid_quantity = parseInt(e.target.value) || 0;
                                                            setBatchData('items', newItems);
                                                        }}
                                                        className="w-full px-2 py-1 text-xs border border-slate-300 rounded-md"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block text-[10px] text-slate-600">Yetkazilgan bonus</label>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max={item.remaining_bonus_quantity}
                                                        value={batchData.items[idx]?.delivered_bonus_quantity || 0}
                                                        onChange={(e) => {
                                                            const newItems = [...batchData.items];
                                                            newItems[idx].delivered_bonus_quantity = parseInt(e.target.value) || 0;
                                                            setBatchData('items', newItems);
                                                        }}
                                                        className="w-full px-2 py-1 text-xs border border-slate-300 rounded-md"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Izoh (ixtiyoriy)</label>
                                    <input
                                        type="text"
                                        value={batchData.notes}
                                        onChange={(e) => setBatchData('notes', e.target.value)}
                                        placeholder="Masalan: 1-partiya qabul qilindi"
                                        className="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg"
                                    />
                                </div>

                                <div className="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                                    <button
                                        type="button"
                                        onClick={() => setIsBatchModalOpen(false)}
                                        className="px-3 py-1.5 border border-slate-300 rounded-lg text-xs"
                                    >
                                        Bekor qilish
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={batchProcessing}
                                        className="px-4 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-semibold hover:bg-indigo-700"
                                    >
                                        {batchProcessing ? 'Saqlanmoqda...' : 'Partiyani saqlash'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* Modal: Record Payment */}
                {isPaymentModalOpen && (
                    <div className="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
                        <div className="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 space-y-4">
                            <div className="flex justify-between items-center border-b border-slate-100 pb-3">
                                <h3 className="text-base font-bold text-slate-900">Yangi to‘lov kiritish (Buxgalteriya)</h3>
                                <button onClick={() => setIsPaymentModalOpen(false)} className="text-slate-400 hover:text-slate-600">
                                    <X className="w-5 h-5" />
                                </button>
                            </div>

                            <form onSubmit={handlePaymentSubmit} className="space-y-4">
                                <div className="bg-amber-50 p-3 rounded-lg text-xs text-amber-800 space-y-1">
                                    <div className="font-bold">5 bosqichli qat’iy tekshiruv tartibi:</div>
                                    <ol className="list-decimal list-inside space-y-0.5 text-[11px]">
                                        <li>Shartnoma Faol yoki Yakunlangan bo‘lishi</li>
                                        <li>Bosqichning to‘lash sharti bajarilgan bo‘lishi</li>
                                        <li>Oldingi bosqich to‘liq to‘langan bo‘lishi</li>
                                        <li>Summa bosqich qoldig‘idan ko‘p emasligi</li>
                                        <li>Smeta moliyaviy yili/oyiga mosligi va smeta qoldig‘idan oshmasligi</li>
                                    </ol>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">To‘lov bosqichi</label>
                                    <select
                                        value={paymentData.milestone_id}
                                        onChange={(e) => setPaymentData('milestone_id', e.target.value)}
                                        className="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                                        required
                                    >
                                        {contract.payment_milestones.map((m) => (
                                            <option key={m.id} value={m.id}>
                                                {m.name} ({m.percentage}%) — Qoldiq: {m.remaining_balance} so‘m ({m.condition_label})
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Smeta qatori (Oy)</label>
                                    <select
                                        value={paymentData.budget_item_id}
                                        onChange={(e) => setPaymentData('budget_item_id', e.target.value)}
                                        className="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                                        required
                                    >
                                        {budget_items.map((b) => (
                                            <option key={b.id} value={b.id}>
                                                {b.financial_year} yil, {b.month_name} ({b.cost_item}) — Smeta qoldig‘i: {b.remaining_balance} so‘m
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 mb-1">Summa (so‘m)</label>
                                        <input
                                            type="text"
                                            value={paymentData.amount}
                                            onChange={(e) => setPaymentData('amount', e.target.value)}
                                            placeholder="Masalan: 750.00"
                                            className="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 mb-1">To‘lov sanasi</label>
                                        <input
                                            type="date"
                                            value={paymentData.payment_date}
                                            onChange={(e) => setPaymentData('payment_date', e.target.value)}
                                            className="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                                            required
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 mb-1">To‘lov raqami</label>
                                        <input
                                            type="text"
                                            value={paymentData.payment_number}
                                            onChange={(e) => setPaymentData('payment_number', e.target.value)}
                                            className="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 mb-1">Izoh</label>
                                        <input
                                            type="text"
                                            value={paymentData.comment}
                                            onChange={(e) => setPaymentData('comment', e.target.value)}
                                            placeholder="Avans to‘lovi"
                                            className="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                                        />
                                    </div>
                                </div>

                                <div className="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                                    <button
                                        type="button"
                                        onClick={() => setIsPaymentModalOpen(false)}
                                        className="px-3 py-1.5 border border-slate-300 rounded-lg text-xs"
                                    >
                                        Bekor qilish
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={paymentProcessing}
                                        className="px-4 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700"
                                    >
                                        {paymentProcessing ? 'Kiritilmoqda...' : 'To‘lovni tasdiqlash'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* Modal: Void Payment */}
                {voidPaymentId && (
                    <div className="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
                        <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
                            <div className="flex justify-between items-center border-b border-slate-100 pb-3">
                                <h3 className="text-base font-bold text-slate-900">To‘lovni bekor qilish</h3>
                                <button onClick={() => setVoidPaymentId(null)} className="text-slate-400 hover:text-slate-600">
                                    <X className="w-5 h-5" />
                                </button>
                            </div>

                            <form onSubmit={handleVoidSubmit} className="space-y-4">
                                <p className="text-xs text-slate-600">
                                    Diqqat: To‘lov o‘chirilmaydi. Yozuv saqlanadi, holati bekor qilingan (voided) bo‘lib, bosqich qoldig‘i va smeta qoldig‘i avtomatik qayta tiklanadi.
                                </p>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">
                                        Bekor qilish sababi (majburiy)
                                    </label>
                                    <textarea
                                        rows="3"
                                        value={voidData.void_reason}
                                        onChange={(e) => setVoidData('void_reason', e.target.value)}
                                        placeholder="Masalan: Buxgalter 1000 o‘rniga adashib kiritgan, aslida 900 edi"
                                        className="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                                        required
                                    />
                                    {voidErrors.void_reason && (
                                        <p className="text-xs text-rose-600 mt-1">{voidErrors.void_reason}</p>
                                    )}
                                </div>

                                <div className="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                                    <button
                                        type="button"
                                        onClick={() => setVoidPaymentId(null)}
                                        className="px-3 py-1.5 border border-slate-300 rounded-lg text-xs"
                                    >
                                        Yopish
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={voidProcessing}
                                        className="px-4 py-1.5 bg-rose-600 text-white rounded-lg text-xs font-semibold hover:bg-rose-700"
                                    >
                                        {voidProcessing ? 'Bekor qilinmoqda...' : 'To‘lovni bekor qilish'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
