import { useState } from 'react'

/**
 *
 * @param value
 * @param onChange
 * @param readOnly
 * @param size
 * @returns {JSX.Element}
 * @constructor
 */
export default function StarRating({ value=0, onChange, readOnly=false, size=18 }) {
    const [hover,setHover]=useState(0); const stars=[1,2,3,4,5]; const display=hover||value
    return (
        <div className="stars" onMouseLeave={()=>setHover(0)}>
            {stars.map(s=>(
                <svg key={s} width={size} height={size} viewBox="0 0 20 20"
                     onMouseEnter={()=>!readOnly&&setHover(s)}
                     onClick={()=>!readOnly&&onChange?.(s)}
                     style={{cursor: readOnly?'default':'pointer'}} aria-label={`${s} star`} role="img">
                    <polygon points="10,1 12.59,6.92 19,7.27 14,11.4 15.45,17.64 10,14.2 4.55,17.64 6,11.4 1,7.27 7.41,6.92"
                             fill={display>=s ? '#f59e0b' : '#e5e7eb'} stroke="#e5e7eb" />
                </svg>
            ))}
        </div>
    )
}
