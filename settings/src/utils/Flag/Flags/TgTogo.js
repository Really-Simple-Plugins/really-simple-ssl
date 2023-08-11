import * as React from "react";
const SvgTgTogo = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="TG_-_Togo_svg__a"
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
    <g mask="url(#TG_-_Togo_svg__a)">
      <path fill="#5EAA22" stroke="#F7FCFF" d="M0-.5h-.5v13h17v-13H0Z" />
      <path
        fill="#FECA00"
        fillRule="evenodd"
        d="M0 3v2h16V3H0ZM0 7v2h16V7H0Z"
        clipRule="evenodd"
      />
      <path fill="#F50101" d="M0 0h8v7H0z" />
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="m4.125 5.072-1.86 1.15.722-1.931L1.5 2.99h1.824l.801-1.925.611 1.925h1.802L5.273 4.29l.623 1.837-1.771-1.056Z"
        clipRule="evenodd"
      />
    </g>
  </svg>
);
export default SvgTgTogo;
