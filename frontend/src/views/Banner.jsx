/**
 *
 * @param total
 * @param lastNice
 * @param onAdd
 * @returns {JSX.Element}
 * @constructor
 */
export default function Banner({total, lastNice, onAdd}) {
    return (
        <header className="banner">
            <div className="banner-inner">
                <h1>Leave a review here!</h1>
                <p>
                    Leave a message here. Unnecessarily hurtful messages or words will <br/> be removed by the editors.
                </p>
                <button className="cta" onClick={onAdd}>Add reaction</button>
                <div className="stats">
                    <div><strong>{total}</strong> reactions</div>
                    <div>last: {lastNice}</div>
                </div>
            </div>
        </header>
    )
}