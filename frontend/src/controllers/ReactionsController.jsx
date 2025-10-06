import { useEffect, useState, startTransition } from 'react'
import {api} from '../services/api'
import Banner from '../views/Banner'
import ReactionForm from '../views/ReactionForm'
import ReactionList from '../views/ReactionList'
import Pagination from '../views/Pagination'
import '../styles.css'

export default function ReactionsController() {
    const [items, setItems] = useState([])
    const [meta, setMeta] = useState({ total:0, perPage:3, page:1, pages:1, last:null })
    const [loading, setLoading] = useState(true)
    const [phase, setPhase] = useState('idle') // 'idle' | 'enter'
    const [error, setError] = useState(null)
    const [showForm, setShowForm] = useState(false)

    /**
     *
     * @param page
     * @param perPage
     * @param smooth
     * @returns {Promise<void>}
     */
    async function load(page = 1, perPage = meta.perPage || 3, smooth = false) {
        setLoading(true)
        setError(null)
        try {
            const data = await api.get(`/api/reactions?limit=${perPage}&page=${page}`)
            startTransition(() => {
                setItems(data.data)
                setMeta(data.meta)
                setPhase('enter')
                setTimeout(() => setPhase('idle'), 200)
            })
        } catch (e) {
            setError(e.message || 'Failed to load')
        } finally {
            setLoading(false)
        }
    }

    useEffect(() => { load(1) }, [])

    const onPage = (p) => {
        // scroll up smoothly & load
        window.scrollTo({ top: 0, behavior: 'smooth' })
        load(p, meta.perPage, true)
    }

    const lastNice = meta.last
        ? new Date(meta.last.replace(' ','T'))
            .toLocaleDateString(undefined,{ weekday:'long', day:'numeric', month:'long', year:'numeric' })
            .toLowerCase()
        : '—'

    return (
        <>
            <Banner total={meta.total} lastNice={lastNice} onAdd={() => setShowForm(true)} />
            <main className="main">
                {showForm && (
                    <ReactionForm onCreated={() => load(meta.page)} onClose={() => setShowForm(false)} />
                )}

                <section className="card" aria-busy={loading ? 'true' : 'false'}>
                    {error && <div className="alert">{error}</div>}
                    <div className={phase === 'enter' ? 'fade-enter fade-enter-active' : ''}>
                        {loading ? <p>Loading…</p> : <ReactionList items={items} />}
                    </div>

                    <Pagination page={meta.page} pages={meta.pages} onPage={onPage} />
                </section>
            </main>
        </>
    )
}
