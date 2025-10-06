import StarRating from './StarRating.jsx'

export default function ReactionList({ items = [] }) {
    if (!items.length) return null

    return (
        <div className="list">
            {items.map((r) => (
                <article key={r.id} className="card">
                    <h3 className="title">{r.title}</h3>
                    <p className="body">{r.message}</p>
                    <div className="star-row">
                        <StarRating value={r.rating} readOnly size={18} />
                    </div>
                    <div className="meta">
                        {r.name} | {new Date(r.created_at.replace(' ', 'T')).toLocaleDateString(
                        undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
                    )}
                    </div>
                </article>
            ))}
        </div>
    )
}