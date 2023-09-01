import * as React from "react";
const SvgLcSaintLucia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="LC_-_Saint_Lucia_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g
      fillRule="evenodd"
      clipRule="evenodd"
      mask="url(#LC_-_Saint_Lucia_svg__a)"
    >
      <path fill="#7CCFF5" d="M0 0h16v12H0V0Z" />
      <path fill="#F7FCFF" d="m8 2 4 8H4l4-8Z" />
      <path fill="#272727" d="m8 4 3.5 6h-7L8 4Z" />
      <path fill="#FECA00" d="m8 7 4 3H4l4-3Z" />
    </g>
  </svg>
);
export default SvgLcSaintLucia;
