import * as React from "react";
const SvgDeGermany = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="DE_-_Germany_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <rect width={16} height={12} fill="#fff" rx={0} />
    </mask>
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#DE_-_Germany_svg__a)">
      <path fill="#FFD018" d="M0 8h16v4H0V8Z" />
      <path fill="#E31D1C" d="M0 4h16v4H0V4Z" />
      <path fill="#272727" d="M0 0h16v4H0V0Z" />
    </g>
  </svg>
);
export default SvgDeGermany;
