import React, { useState } from 'react';
import { usePage, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { 
    Wallet, 
    Plus, 
    Calendar, 
    TrendingUp, 
    CheckCircle2, 
    X,
    FileSpreadsheet
} from 'lucide-react';

export default function BudgetIndex({ budget_items = [] }) {
    const { auth } = usePage().props;
    const isAccountant = auth?.user?.role === 'accountant';

    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        financial_year: 2026,
        cost_item: 'Dori vositalari',
        month: 5,
        allocated_amount: '1500.00',
        notes: ''
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/budget', {
            onSuccess: () => {
                setIsCreateModalOpen(false);
                reset();
            }
        });
    };

    const totalAllocated = budget_items.reduce((acc, item) => acc + parseFloat(item.allocated_amount || 0), 0);
    const totalPaid = budget_items.reduce((acc, item) => acc + parseFloat(item.paid_amount || 0), 0);
    const totalRemaining = totalAllocated - totalPaid;

    return (
        <AppLayout>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold text-slate-900 tracking-tight">Smeta (Byudjet) qoldiqlari</h2>
                        <p className="text-sm text-slate-500 mt-1">
                            Tashkilot xarajat moddalari va oylar kesimida ajratilgan mablag‘lar hamda amaldagi to‘lov qoldiqlari
                        </p>
                    </div>

                    {isAccountant && (
                        <button
                            onClick={() => setIsCreateModalOpen(true)}
                            className="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                        >
                            <Plus className="w-4 h-4 mr-1.5" />
                            Yangi smeta qatori
                        </button>
                    )}
                </div>

                {/* Summary Metrics */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-xs">
                        <span className="text-xs font-semibold text-slate-500 uppercase">Jami ajratilgan smeta</span>
                        <div className="text-xl font-bold text-slate-900 mt-1">
                            {totalAllocated.toFixed(2)} so‘m
                        </div>
                        <span className="text-[11px] text-slate-400">Yil bo‘yicha ajratilgan mablag‘</span>
                    </div>

                    <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-xs">
                        <span className="text-xs font-semibold text-slate-500 uppercase">Amaldagi to‘lovlar</span>
                        <div className="text-xl font-bold text-blue-600 mt-1">
                            {totalPaid.toFixed(2)} so‘m
                        </div>
                        <span className="text-[11px] text-slate-400">Faol to‘lovlar summasi</span>
                    </div>

                    <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-xs">
                        <span className="text-xs font-semibold text-slate-500 uppercase">Jami erkin qoldiq</span>
                        <div className="text-xl font-bold text-emerald-600 mt-1">
                            {totalRemaining.toFixed(2)} so‘m
                        </div>
                        <span className="text-[11px] text-slate-400">Yangi to‘lovlar uchun mavjud limit</span>
                    </div>
                </div>

                {/* Budget Lines Table */}
                <div className="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
                    <div className="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                        <h3 className="text-sm font-bold text-slate-900">Oylar kesimidagi smeta qatorlari</h3>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200 text-xs">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-6 py-3.5 text-left font-semibold text-slate-600 uppercase">Moliyaviy yil</th>
                                    <th className="px-6 py-3.5 text-left font-semibold text-slate-600 uppercase">Xarajat moddasi</th>
                                    <th className="px-6 py-3.5 text-left font-semibold text-slate-600 uppercase">Oy</th>
                                    <th className="px-6 py-3.5 text-right font-semibold text-slate-600 uppercase">Ajratilgan summa</th>
                                    <th className="px-6 py-3.5 text-right font-semibold text-slate-600 uppercase">To‘langan summa</th>
                                    <th className="px-6 py-3.5 text-right font-semibold text-slate-600 uppercase">Erkin qoldiq</th>
                                    <th className="px-6 py-3.5 text-left font-semibold text-slate-600 uppercase">Izoh</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {budget_items.length === 0 ? (
                                    <tr>
                                        <td colSpan="7" className="px-6 py-12 text-center text-slate-500">
                                            Hozircha smeta ma’lumotlari mavjud emas.
                                        </td>
                                    </tr>
                                ) : (
                                    budget_items.map((b) => (
                                        <tr key={b.id} className="hover:bg-slate-50 transition">
                                            <td className="px-6 py-4 font-semibold text-slate-900">{b.financial_year}</td>
                                            <td className="px-6 py-4 font-medium text-slate-900">{b.cost_item}</td>
                                            <td className="px-6 py-4 text-slate-700 font-semibold">{b.month_name}</td>
                                            <td className="px-6 py-4 text-right text-slate-800 font-semibold">{b.allocated_amount} so‘m</td>
                                            <td className="px-6 py-4 text-right text-blue-700 font-semibold">{b.paid_amount} so‘m</td>
                                            <td className="px-6 py-4 text-right font-bold text-emerald-700">{b.remaining_balance} so‘m</td>
                                            <td className="px-6 py-4 text-slate-500">{b.notes || '—'}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Modal: Create Budget Line */}
                {isCreateModalOpen && (
                    <div className="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
                        <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
                            <div className="flex justify-between items-center border-b border-slate-100 pb-3">
                                <h3 className="text-base font-bold text-slate-900">Yangi smeta qatori qo‘shish</h3>
                                <button onClick={() => setIsCreateModalOpen(false)} className="text-slate-400 hover:text-slate-600">
                                    <X className="w-5 h-5" />
                                </button>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 mb-1">Moliyaviy yil</label>
                                        <input
                                            type="number"
                                            value={data.financial_year}
                                            onChange={(e) => setData('financial_year', parseInt(e.target.value) || 2026)}
                                            className="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 mb-1">Oy (1-12)</label>
                                        <select
                                            value={data.month}
                                            onChange={(e) => setData('month', parseInt(e.target.value))}
                                            className="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg"
                                            required
                                        >
                                            <option value={1}>1 - Yanvar</option>
                                            <option value={2}>2 - Fevral</option>
                                            <option value={3}>3 - Mart</option>
                                            <option value={4}>4 - Aprel</option>
                                            <option value={5}>5 - May</option>
                                            <option value={6}>6 - Iyun</option>
                                            <option value={7}>7 - Iyul</option>
                                            <option value={8}>8 - Avgust</option>
                                            <option value={9}>9 - Sentabr</option>
                                            <option value={10}>10 - Oktabr</option>
                                            <option value={11}>11 - Noyabr</option>
                                            <option value={12}>12 - Dekabr</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Xarajat moddasi</label>
                                    <input
                                        type="text"
                                        value={data.cost_item}
                                        onChange={(e) => setData('cost_item', e.target.value)}
                                        placeholder="Masalan: Dori vositalari"
                                        className="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg"
                                        required
                                    />
                                    {errors.cost_item && <p className="text-xs text-rose-600 mt-1">{errors.cost_item}</p>}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Ajratilgan summa (so‘m)</label>
                                    <input
                                        type="text"
                                        value={data.allocated_amount}
                                        onChange={(e) => setData('allocated_amount', e.target.value)}
                                        placeholder="Masalan: 3000.00"
                                        className="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg"
                                        required
                                    />
                                    {errors.allocated_amount && <p className="text-xs text-rose-600 mt-1">{errors.allocated_amount}</p>}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Izoh</label>
                                    <input
                                        type="text"
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        placeholder="Qo‘shimcha izoh"
                                        className="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg"
                                    />
                                </div>

                                <div className="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                                    <button
                                        type="button"
                                        onClick={() => setIsCreateModalOpen(false)}
                                        className="px-3 py-1.5 border border-slate-300 rounded-lg text-xs"
                                    >
                                        Bekor qilish
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-4 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700"
                                    >
                                        {processing ? 'Saqlanmoqda...' : 'Smetani saqlash'}
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
