import {useEffect, useState} from 'react'
import {api} from '../services/api'
import Banner from '../views/Banner'
import ReactionForm from '../views/ReactionForm'
import ReactionList from '../views/ReactionList'
import Pagination from '../views/Pagination'
import '../styles.css'

export default function ReactionsController() {
    const [items, setItems] = useState([])
    const [meta, setMeta] = useState({total: 0, perPage: 3, page: 1, pages: 1, last: null})
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState(null)
    const [showForm, setShowForm] = useState(false)

    /**
     *
     * @param page
     * @param perPage
     * @returns {Promise<void>}
     */
    async function load(page = 1, perPage = meta.perPage || 3) {
        setLoading(true);
        setError(null)
        try {
            const data = await api.get(`/api/reactions?limit=${perPage}&page=${page}`)
            setItems(data.data)
            setMeta(data.meta)
        } catch (e) {
            setError(e.message || 'Failed to load')
        } finally {
            setLoading(false)
        }
    }

    useEffect(() => {
        load(1)
    }, [])

    const lastNice = meta.last
        ? new Date(meta.last.replace(' ', 'T')).toLocaleDateString(undefined, {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        }).toLowerCase()
        : '—'

    return (
        <>
            <Banner total={meta.total} lastNice={lastNice} onAdd={() => setShowForm(true)}/>
            <main className="main">
                {showForm && (
                    <ReactionForm
                        onCreated={() => load(meta.page)}
                        onClose={() => setShowForm(false)}
                    />
                )}
                <section className="card">
                    {error && <div className="alert">{error}</div>}
                    {loading ? <p>Loading…</p> : <ReactionList items={items}/>}
                    <Pagination page={meta.page} pages={meta.pages} onPage={p => load(p)}/>
                </section>
            </main>
        </>
    )
}
