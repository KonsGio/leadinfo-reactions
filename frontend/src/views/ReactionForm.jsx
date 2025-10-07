import {useState} from 'react'
import StarRating from './StarRating.jsx'
import {api} from '../services/api'

/**
 *
 * @param onCreated
 * @param onClose
 * @returns {JSX.Element}
 * @constructor
 */
export default function ReactionForm({onCreated, onClose}) {
    const [form, setForm] = useState({name: '', email: '', title: '', message: '', rating: 0})
    const [errors, setErrors] = useState({})
    const [busy, setBusy] = useState(false)
    const [apiError, setApiError] = useState(null)

    const update = (key) => (e) => {
        setForm((f) => ({...f, [key]: e.target.value}))
        setErrors((err) => ({...err, [key]: undefined}))
    }
    const updateRating = (v) => {
        setForm((f) => ({...f, rating: v}))
        setErrors((err) => ({...err, rating: undefined}))
    }

    /**
     *
     * @param e
     * @returns {Promise<void>}
     */
    async function submit(e) {
        e.preventDefault()
        setBusy(true)
        setApiError(null)
        setErrors({})

        try {
            await api.post('/api/reactions', form)
            setForm({name: '', email: '', title: '', message: '', rating: 0})
            onCreated?.()
            onClose?.()
        } catch (err) {
            if (err.status === 422 && err.data?.errors) {
                setErrors(err.data.errors)          // <-- show server messages under fields
            } else {
                setApiError(err.message || 'Something went wrong.')
            }
        } finally {
            setBusy(false)
        }
    }

    return (
        <div className="formWrap" role="dialog" aria-modal="true" aria-label="Add reaction form">
            <form onSubmit={submit} noValidate className="formGrid">
                <label htmlFor="name">Name</label>
                <div className="full">
                    <input
                        id="name"
                        type="text"
                        className="input"
                        value={form.name}
                        onChange={update('name')}
                        placeholder="Your full name"
                        aria-invalid={!!errors.name}
                        aria-describedby={errors.name ? 'err-name' : undefined}
                    />
                    {errors.name && <p id="err-name" className="error">{errors.name}</p>}
                </div>

                <label htmlFor="email">Email</label>
                <div className="full">
                    <input
                        id="email"
                        type="email"
                        className="input"
                        value={form.email}
                        onChange={update('email')}
                        placeholder="you@example.com"
                        aria-invalid={!!errors.email}
                        aria-describedby={errors.email ? 'err-email' : undefined}
                    />
                    {errors.email && <p id="err-email" className="error">{errors.email}</p>}
                </div>

                <label htmlFor="title">Title</label>
                <div className="full">
                    <input
                        id="title"
                        type="text"
                        className="input"
                        value={form.title}
                        onChange={update('title')}
                        placeholder="Short title"
                        aria-invalid={!!errors.title}
                        aria-describedby={errors.title ? 'err-title' : undefined}
                    />
                    {errors.title && <p id="err-title" className="error">{errors.title}</p>}
                </div>

                <label htmlFor="message">Short message</label>
                <div className="full">
          <textarea
              id="message"
              className="input"
              rows={6}
              value={form.message}
              onChange={update('message')}
              placeholder="Say a few words…"
              aria-invalid={!!errors.message}
              aria-describedby={errors.message ? 'err-message' : undefined}
          />
                    {errors.message && <p id="err-message" className="error">{errors.message}</p>}
                </div>

                <label>Rating</label>
                <div className="star-row">
                    {/* IMPORTANT: use updateRating, not update('message') */}
                    <StarRating value={form.rating} size={20} onChange={updateRating}/>
                    {errors.rating && <p className="error" style={{marginLeft: 8}}>{errors.rating}</p>}
                </div>

                {apiError && <div className="error full" role="alert">{apiError}</div>}

                <div className="formActions full">
                    <button type="submit" className="btn primary" disabled={busy}>
                        {busy ? 'Submitting…' : 'Add reaction'}
                    </button>
                    <button type="button" className="btn ghost" onClick={onClose}>Cancel</button>
                </div>
            </form>
        </div>
    )
}