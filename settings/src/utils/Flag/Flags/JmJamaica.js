import * as React from "react";
const SvgJmJamaica = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="JM_-_Jamaica_svg__a"
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
    <g mask="url(#JM_-_Jamaica_svg__a)">
      <path
        fill="#093"
        fillRule="evenodd"
        d="M0 0h16v12H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#272727"
        stroke="#FECA00"
        strokeWidth={1.35}
        d="m-.07-.52-1.105-.912v14.864l1.105-.911 7.269-6L7.829 6l-.63-.52-7.27-6Z"
      />
      <path
        fill="#272727"
        stroke="#FECA00"
        strokeWidth={1.35}
        d="m16.082-.53 1.093-.862V13.392l-1.093-.862-7.61-6L7.8 6l.673-.53 7.61-6Z"
      />
    </g>
  </svg>
);
export default SvgJmJamaica;
