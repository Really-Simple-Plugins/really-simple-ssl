import * as React from "react";
const SvgStSaoTomeAndPrincipe = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="ST_-_Sao_Tome_and_Principe_svg__a"
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
      mask="url(#ST_-_Sao_Tome_and_Principe_svg__a)"
    >
      <path fill="#FBCD17" d="M0 0v12h16V0H0Z" />
      <path fill="#73BE4A" d="M0 0v4h16V0H0ZM0 8v4h16V8H0Z" />
      <path fill="#C51918" d="M0 0v12l5-6-5-6Z" />
      <path
        fill="#272727"
        d="m8.5 6.935-.934.565.213-1.102L7 5.573l1.055-.044L8.5 4.5l.446 1.029H10l-.777.87.234 1.101-.956-.565ZM12.5 6.935l-.934.565.213-1.102L11 5.573l1.055-.044L12.5 4.5l.446 1.029H14l-.777.87.234 1.101-.956-.565Z"
      />
    </g>
  </svg>
);
export default SvgStSaoTomeAndPrincipe;
