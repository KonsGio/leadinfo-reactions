/**
 * 
 * @param page
 * @param pages
 * @param onPage
 * @returns {JSX.Element|null}
 * @constructor
 */
export default function Pagination({page, pages, onPage}) {
    if (pages <= 1) return null
    const prev = Math.max(1, page - 1)
    const next = Math.min(pages, page + 1)

    return (
        <div className="pagination">
            <span>Page {page} of {pages}</span>
            <div className="pager" role="navigation" aria-label="pagination">
                <button className="pagebtn" onClick={() => onPage(prev)} disabled={page === 1} aria-label="Previous">‹
                </button>
                {Array.from({length: pages}, (_, i) => i + 1).map(n => (
                    <button key={n} className="pagebtn" aria-current={n === page ? 'true' : 'false'}
                            onClick={() => onPage(n)}>{n}</button>
                ))}
                <button className="pagebtn" onClick={() => onPage(next)} disabled={page === pages} aria-label="Next">›
                </button>
            </div>
        </div>
    )
}