import * as React from "react";
const SvgEhWesternSahara = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="EH_-_Western_Sahara_svg__a"
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
      mask="url(#EH_-_Western_Sahara_svg__a)"
    >
      <path fill="#F7FCFF" d="M0 0h16v12H0V0Z" />
      <path fill="#272727" d="M0 0v4h16V0H0Z" />
      <path fill="#5EAA22" d="M0 8v4h16V8H0Z" />
      <path
        fill="#E31D1C"
        d="m0 0 8 6-8 6V0ZM10.844 7.739S9.707 7.05 9.707 5.915c0-1.136 1.137-1.733 1.137-1.733-.51-.323-2.274.138-2.274 1.77 0 1.633 1.748 1.934 2.274 1.787Zm.92-2.083-.662-.62.229.792-.64.303.786.256.342.858.15-.794.776.172-.588-.575.2-.612-.593.22Z"
      />
    </g>
  </svg>
);
export default SvgEhWesternSahara;
